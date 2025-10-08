// tests/Feature/EnvLiteTest.php（Pest例）
it('GET /medical/recept_audit/env-lite returns expected JSON', function () {
    $res = http('GET', 'https://beautifulsnow.co.jp/medical/recept_audit/env-lite');
    expect($res->status)->toBe(200);
    expect($res->json())->toMatchArray([
        'ok'   => true,
        'kind' => 'env-lite',
    ]);
});
