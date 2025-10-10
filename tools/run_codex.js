"use strict";

const fs = require("fs");
const path = require("path");

// Node 18+ は fetch が同梱
const fetchFn = global.fetch;

const API_KEY = process.env.OPENAI_API_KEY;
const MODEL   = process.env.OPENAI_MODEL || "gpt-4o-mini";
const PROMPT  = process.env.PROMPT_FILE;
const PATCH   = process.env.PATCH_FILE;

if (!API_KEY) {
  console.error("missing OPENAI_API_KEY");
  process.exit(1);
}
if (!PROMPT || !fs.existsSync(PROMPT)) {
  console.error("prompt not found: " + PROMPT);
  process.exit(1);
}

const systemMsg = [
  "You output ONLY a unified diff (patch).",
  "No explanations, no markdown fences, no prose.",
  "Output must start with 'diff --git '.",
  "For new files, include full file contents in the patch."
].join(" ");

const userMsg = fs.readFileSync(PROMPT, "utf8");

async function main() {
  const body = {
    model: MODEL,
    messages: [
      { role: "system", content: systemMsg },
      { role: "user",   content: userMsg }
    ],
    temperature: 0.1
  };

  const res = await fetchFn("https://api.openai.com/v1/chat/completions", {
    method: "POST",
    headers: {
      "Authorization": "Bearer " + API_KEY,
      "Content-Type": "application/json"
    },
    body: JSON.stringify(body)
  });

  if (!res.ok) {
    console.error("OpenAI API error: " + res.status);
    const t = await res.text().catch(() => "");
    if (t) console.error(t);
    process.exit(2);
  }

  const json = await res.json();
  let out =
    json?.choices?.[0]?.message?.content ?? "";

  // 万一コードフェンスが返ってきた時の保険
  const m = out.match(/```(?:diff|patch)?\n([\s\S]*?)```/);
  if (m && m[1]) out = m[1];

  // 改行正規化
  out = out.replace(/\r/g, "");

  if (!/^diff --git /m.test(out)) {
    console.error("model did not return a unified diff");
    process.exit(4);
  }

  fs.mkdirSync(path.dirname(PATCH), { recursive: true });
  fs.writeFileSync(PATCH, out.endsWith("\n") ? out : out + "\n", "utf8");
  console.log("patch written:", PATCH);
}

main().catch(err => {
  console.error(err);
  process.exit(3);
});
