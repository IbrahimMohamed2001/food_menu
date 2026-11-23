<?php

it('returns a generic JSON 404 for unknown API routes', function () {
    $this->getJson('/api/v1/does-not-exist')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Not found.']);
});
