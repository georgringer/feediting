<?php
declare(strict_types=1);

namespace GeorgRinger\Feediting\Event;

use Psr\Http\Message\ServerRequestInterface;

final class EditPanelActionEvent
{

    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly int $permissionsOfPage,
        public readonly string $table,
        public readonly int $id,
        public readonly array $row,
        protected array $actions,
    )
    {
    }

    public function getActions(): array
    {
        return $this->actions;
    }

    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }

    public function addAction(string $action): void
    {
        $this->actions[] = $action;
    }

}