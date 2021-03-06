<?php

declare(strict_types=1);

namespace Rector\BetterPhpDocParser\Comment;

use PhpParser\Node;

final class MergedNodeCommentPreserver
{
    public function keepComments(Node $newNode, Node ...$mergedNodes): void
    {
        $comments = [];

        foreach ($mergedNodes as $mergedNode) {
            $comments = array_merge($comments, $mergedNode->getComments());
        }

        if ($comments === []) {
            return;
        }

        $comments = array_merge($newNode->getComments(), $comments);
        $newNode->setAttribute('comments', $comments);
    }
}
