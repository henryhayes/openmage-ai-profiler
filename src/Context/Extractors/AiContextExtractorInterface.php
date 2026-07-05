<?php

interface AiContextExtractorInterface
{
    public function extract(AiContext $context, array $data);
}
