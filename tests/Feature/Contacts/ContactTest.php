<?php

namespace Tests\Feature\Contacts;

use App\Mail\ContactFormMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_can_submit_valid_data()
    {
        Mail::fake();

        $response = $this->post(route('contact.send'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08123456789',
            'message' => 'Halo, saya ingin bertanya.',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        Mail::assertSent(ContactFormMail::class, function ($mail) {
            return $mail->hasTo('qasysyams23@gmail.com') &&
                   $mail->data['name'] === 'John Doe';
        });
    }

    public function test_contact_form_rejects_invalid_data()
    {
        Mail::fake();

        $response = $this->post(route('contact.send'), [
            'name' => '',
            'email' => 'not-an-email',
            'phone' => 'abc',
            'message' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'phone', 'message']);
        Mail::assertNothingSent();
    }
}
