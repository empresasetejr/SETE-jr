<?php

namespace Models;

use Core\Model;

class ChatbotOption extends Model
{
    protected string $table = 'chatbot_options';

    public function findActiveResponse(string $option): ?string
    {
        $row = $this->findOne([
            'option_number' => $option,
            'active' => 1,
        ], ['response']);

        return $row['response'] ?? null;
    }
}

