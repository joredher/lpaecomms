<?php

namespace Lpaecomms\Controllers;

use Lpaecomms\Repositories\BaseRepository;
use function Lpaecomms\Helpers\sendEmailToCustomerService;

class ContactController
{
    public function send()
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (!$name || !$email || !$phone || !$message) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'All fields are required.'
            ];
            header('Location: /contact');
            exit;
        }

        $sent = sendEmailToCustomerService(
            ['name' => $name, 'email' => $email, 'phone' => $phone, 'message' => $message],
        );

        $_SESSION['flash_message'] = [
            'type' => $sent ? 'success' : 'danger',
            'message' => $sent
                ? 'Your message has been sent successfully.'
                : 'There was a problem sending your message.'
        ];

        header('Location: /contact');
        exit;
    }

}