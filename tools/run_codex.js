#!/usr/bin/env node
/**
 * Run Codex via OpenAI Responses API and write a unified diff to ai/patch.diff.
 * - No external deps (Node >=18 with global fetch).
 * - PROMPT_FILE (default: ai/prompt.md)
 * - PATCH_FILE  (default: ai/patch.diff)
 * - model: INPUT_MODEL > MODEL_DEFAULT > 'gpt-4.1-mini'
 */

const fs = require("fs/promises");
const path = require("path");

async function main() {
  const apiKey = process.env.OPENAI_API_KEY || "";
  if (!apiKey) {
    console.error("ERROR: OPENAI_API_KEY is empty."); process.exit(2);
  }

  const model =
    process.env.INPUT_MODEL ||
    process.env.MODEL_DEFAULT ||
    "gpt-4.1-mini";

  const promptFile = process.env.PROMPT_FILE || "ai/prompt.md";
  const patchFile  = process.env.PATCH_FILE  || "ai/patch.diff";

  let userPrompt = "";
  try {
    userPrompt = await fs.readFile(promptFile, "utf-8");
  } catch (e) {
    console.error(`ERROR: failed to read ${promptFile}`); console.error(e);
    process.exit(2);
  }

  const systemPrompt = [
    "You are a rigorous code generation engine.",
    "Return ONLY a unified diff (git patch) that applies cleanly to the current repository.",
    "No explanations, no code fences. UTF-8, POSIX newlines."
  ].join(" ");

  const body = {
    model,
    input: [
      { role: "system", content: systemPrompt },
      { role: "user",   content: userPrompt }
    ],
    temperature: 0.1,
  };

  const res = await fetch("https://api.openai.com/v1/responses", {
    method: "POST",
    headers: {
      "Authorization": `Bearer ${apiKey}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify(body),
  });

  if (!res.ok) {
    const txt = await res.text().catch(()=>"");
    console.error(`ERROR: OpenAI API failed (status ${res.status}).`);
    console.error(txt);
    process.exit(3);
  }

  const data = await res.json();

  // Responses API からテキスト抽出（出力形式の揺れに耐性）
  const text =
    data.output_text ||
    (Array.isArray(data.output)
      ? data.output.map(o => (o?.content?.[0]?.text?.value) || "").join("")
      : "") ||
    (data.choices?.[0]?.message?.content || "");

  if (!text || (!/\ndiff --git /.test(text) && !/^--- /.test(text))) {
    console.error("ERROR: model did not return a unified diff.");
    console.error(String(text).slice(0, 600));
    process.exit(4);
  }

  await fs.mkdir(path.dirname(patchFile), { recursive: true });
  await fs.writeFile(patchFile, text, "utf-8");
  console.log(`Wrote patch: ${patchFile} (${text.length} bytes)`);
}

main().catch(e => { console.error("FATAL:", e); process.exit(1); });
