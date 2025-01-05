<?php

namespace App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecoverEmail extends Mailable {

    use Queueable,
        SerializesModels;

    /**
     * Instance of User
     *
     * @var User
     */
    public $model;

    /**
     * Create a new message instance.
     *
     * @param User $user
     */
    public function __construct($model) {
        $this->model = $model;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build() {
        $subject = $this->model->subject;
        $template = $this->model->data['template'];
        $attachment = !empty($this->model->data['attachment']) ? $this->model->data['attachment'] : null;
        $data = $this->model->data;
        return $this->subject($subject)->view($template, compact('data'));
//        return $this->subject($subject)->view($template, compact('data'))->attach($attachment, ['as' => 'label.pdf', 'mime' => 'application/pdf']);
    }

}
