<?php

namespace Tests\Feature;

use App\Http\Requests\StoreComplaintReplyRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreComplaintReplyRequestTest extends TestCase
{
    public function test_it_rejects_missing_and_blank_reply_bodies(): void
    {
        foreach ([
            [],
            ['body' => ''],
            ['body' => '   '],
        ] as $data) {
            $validator = Validator::make(
                $data,
                (new StoreComplaintReplyRequest)->rules(),
            );

            $this->assertTrue($validator->errors()->has('body'));
        }
    }

    public function test_it_accepts_a_valid_reply_body(): void
    {
        $validator = Validator::make(
            ['body' => 'Here is the additional information you requested.'],
            (new StoreComplaintReplyRequest)->rules(),
        );

        $this->assertFalse($validator->fails());
    }
}
