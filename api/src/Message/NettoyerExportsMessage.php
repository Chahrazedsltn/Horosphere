<?php

namespace App\Message;

final class NettoyerExportsMessage
{
    public function __construct(
        public readonly int $retentionJours = 30,
    ) {}
}
