<?php

namespace WebmanTech\AmisAdmin\Controller;

use Throwable;
use Webman\Http\Request;
use Webman\Http\Response;
use WebmanTech\AmisAdmin\JsonPage;

class JsonPageController
{
    public function show(Request $request, string $page): Response|string
    {
        try {
            $schema = JsonPage::loadSchema($page, $request);
        } catch (Throwable $e) {
            if ($request->get('_ajax')) {
                return amis_response([], $e->getMessage());
            }

            $code = (int)$e->getCode();
            if (!in_array($code, [400, 404], true)) {
                $code = 500;
            }

            $title = match ($code) {
                400 => '400 Bad Request',
                404 => '404 Not Found',
                default => '500 Server Error',
            };

            $message = htmlspecialchars($e->getMessage(), ENT_QUOTES);
            return response("<h1>{$title}</h1><pre>{$message}</pre>", $code);
        }

        if ($request->get('_ajax')) {
            return amis_response($schema);
        }

        $title = (string)($schema['title'] ?? '');
        if ($title === '') {
            $title = $page;
        }

        return amis()->renderPage($title, $schema);
    }
}

