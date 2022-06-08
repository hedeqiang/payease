<?php

namespace Hedeqiang\Yizhifu;

interface PayInterface
{
    public function request($path, $params): array;

    public function handleNotify(): array;
}

