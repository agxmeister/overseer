<?php

namespace Watch\Schedule;

class Builder
{
    public function getTree($issues): Node
    {
        $node = new Node('finish');
        foreach ($issues as $issue) {
            $node->link(new Node($issue['key']));
        }
        return $node;
    }
}
