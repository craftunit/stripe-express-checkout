<?php

namespace craftunit\craftstripeexpresscheckout\interfaces;

interface EventHandlerInterface
{
    /**
     * Return an array of triggers for this event handler
     *
     * @return array $triggers
     */
    public function triggers(): array;

    /**
     * Handles an incoming event
     *
     * @param $event
     */
    public function handle($event);
}
