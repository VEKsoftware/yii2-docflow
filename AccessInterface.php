<?php

namespace statuses;

/**
 * This interface is used to get control of user access
 */
interface AccessInterface
{
    /**
     * Реализуя данный метод, необходимо в вашем приложении определить правила доступа для операций
     * с моделями модуля Statuses, список  всех операций:
     *
     * /// Модель Statuses
     * statuses.statuses.create
     * statuses.statuses.view
     * statuses.statuses.update
     * statuses.statuses.delete
     *
     * /// Модель StatusesDoctypes
     * statuses.doctypes.create
     * statuses.doctypes.view
     * statuses.doctypes.update
     * statuses.doctypes.delete
     *
     * /// Модель StatusesLinks
     * statuses.link.create
     * statuses.link.delete
     *
     * @param string $operation Операция, к которой необходимо проверить доступ.
     * @return bool
     */
    public function isAllowed($operation);
}
