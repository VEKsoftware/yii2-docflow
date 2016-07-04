<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 01.07.16
 * Time: 15:36
 *
 * Содержит сообщения, выдаваемый исключениями в поведениях
 *
 * U - универсальный контекст, может содержаться в любом поведении
 * B - сообщения для LinkBaseBehavior
 * SL - сообщения для LinkSimpleBehavior
 * STR - сообщения для LinkStructureBehavior
 * ORD - сообщения для LinkOrderedBehavior
 * STAT - сообщения для StatusBehavior
 */

namespace docflow\messages\behaviors;

use yii\base\Model;

class BehaviorsMessages extends Model
{
    const U_OWNER_ID_NULL_OR_NOT_INT = 'Текущий документ (owner) пуст';
    const U_IF_SET_LINK_BY_SELF = 'Нельзя назначить связь на себя';


    const B_OWNER_NOT_DOCUMENT = 'Класс узла не принадлежит Document';
    const B_LINK_CLASS_EMPTY_OR_NOT_STRING = 'Отсутствует наимнование класса связи или не строкового типа';
    const B_DOCUMENT_QUERY_NULL_OR_NOT_INSTANCEOF_CLOSURE = 'Запрос на поиск документов не определен или не принадлежит Closure';


    const SL_DOCUMENT_OWNER_NOT_HAS_AVAILABLE = 'Документ, у которого получаем простые связи, не содержится в списке доступных документов';
    const SL_DOCUMENTS_ARRAY_EMPTY = 'Массив с объектами документов пуст';

    const SL_IS_SET = 'Простая связь уже существует';
    const SL_NOT_SET = 'Простая связь не найдена';

    const SL_DOCUMENT_FROM_SET_NOT_HAS_AVAILABLE = 'Документ, от которого устанавливаем простую связь, не содержится в списке доступных документов';
    const SL_DOCUMENT_FROM_SET_NODE_ID_EMPTY_OR_NOT_INT = 'Документ, от которого устанавливаем простую связь, не содержит данных';

    const SL_DOCUMENT_TO_SET_NODE_ID_EMPTY_OR_NOT_INT = 'Документ, к которому устанавливаем простую связь, не содержит данных';
    const SL_DOCUMENT_TO_SET_NOT_INSTANCEOF_DOCUMENT = 'Документ, к которому устанавливаем простую связь, не является наследником Document';
    const SL_DOCUMENT_TO_SET_NOT_HAS_AVAILABLE = 'Документ, к которому устанавливаем простую связь, не содержится в списке доступных документов';

    const SL_DOCUMENT_FROM_DEL_NODE_ID_EMPTY_OR_NOT_INT = 'Документ, от которого удаляем простую связь, не содержит данных';
    const SL_DOCUMENT_FROM_DEL_NOT_HAS_AVAILABLE = 'Документ, от которого удаляем простую связь, не содержится в списке доступных документов';

    const SL_DOCUMENT_TO_DEL_NOT_INSTANCEOF_DOCUMENT = 'Документ, к которому удаляем простую связь, не является наследником Document';
    const SL_DOCUMENT_TO_DEL_NODE_ID_EMPTY_OR_NOT_INT = 'Документ, к которому удаляем простую связь, не содержит данных';
    const SL_DOCUMENT_TO_DEL_NOT_HAS_AVAILABLE = 'Документ, к которому удаляем простую связь, не содержится в списке доступных документов';


    const STR_PARENT_LINK_1_LVL_NOT_SET = 'Родительская связь отсутствует';

    const STR_DOCUMENT_OWNER_DEL_PARENT_NOT_HAS_AVAILABLE = 'Документ, у которого удаляем родителей, не содержится в списке доступных документов';
    const STR_DOCUMENT_OWNER_GET_PARENT_NOT_HAS_AVAILABLE = 'Документ, у которого получаем родителей, не содержится в списке доступных документов';
    const STR_DOCUMENT_OWNER_GET_CHILD_NOT_HAS_AVAILABLE = 'Документ, у которого получаем ребенка, не содержится в списке доступных документов';

    const STR_DOCUMENT_FROM_SET_PARENT_NOT_HAS_AVAILABLE = 'Документ, у которого устанавливаем нового родителя, не содержится в списке доступных документов';
    const STR_DOCUMENT_FROM_SET_CHILD_NOT_HAS_AVAILABLE = 'Документ, к которому устанавливаем нового ребенка, не содержится в списке доступных документов';

    const STR_DOCUMENT_TO_SET_PARENT_NODE_ID_EMPTY_OR_NOT_INT = 'Документ, устанавливаемый новым родителем, не содержит данных';
    const STR_DOCUMENT_TO_SET_PARENT_NOT_INSTANCEOF_DOCUMENT = 'Документ, устанавливаемый новым родителем, не является наследником Document';
    const STR_DOCUMENT_TO_SET_PARENT_NOT_HAS_AVAILABLE = 'Документ, устанавливаемый новым родителем, не содержится в списке доступных документов';

    const STR_DOCUMENT_TO_SET_CHILD_NODE_ID_EMPTY_OR_NOT_INT = 'Документ, добавляемый новым ребенком, не содержит данных';
    const STR_DOCUMENT_TO_SET_CHILD_NOT_INSTANCEOF_DOCUMENT = 'Документ, добавляемый новым ребенком, не является наследником Document';
    const STR_DOCUMENT_TO_SET_CHILD_NOT_HAS_AVAILABLE = 'Документ, добавляемый новым ребенком, не содержится в списке доступных документов';

    const STR_DENIED_SET_ONE_OF_PARENTS_HOW_CHILD = 'Нельзя устанавливать родителя текущего документа ребенком';
    const STR_DENIED_SET_ONE_OF_CHILDES_HOW_PARENT = 'Нельзя устанавливать ребенка текущего документа родителем';

    const STR_NEW_PARENT_IS_CURRENT = 'Новый родитель является текущим';
    const STR_NEW_CHILD_IS_CURRENT = 'Новый ребенок уже присутствует';


    const ORD_DOCUMENT_ORDER_UP_NOT_HAS_AVAILABLE = 'Документ, у которого повышаем позицию статуса, не содержится в списке доступных документов';
    const ORD_DOCUMENT_ORDER_DOWN_NOT_HAS_AVAILABLE = 'Документ, у которого понижаем позицию статуса, не содержится в списке доступных документов';
    const ORD_DOCUMENT_LEVEL_UP_NOT_HAS_AVAILABLE = 'Документ, у которого понижаем уровень вложения, не содержится в списке доступных документов';
    const ORD_DOCUMENT_LEVEL_DOWN_NOT_HAS_AVAILABLE = 'Документ, у которого понижаем уровень вложения, не содержится в списке доступных документов';

    const ORD_POSITION_NOT_CHANGE = 'Позиция не изменена';
    const ORD_POSITION_CAN_NOT_CHANGE = 'Позиция не может быть изменена';

    const STAT_OWNER_NOT_INSTANCEOF_DOCUMENT = 'You can attach StatusesBehavior only to instances of docflow\models\Document';
    const STAT_PROPERTY_STATUS_ROOT_TAG_IS_EMPTY = 'StatusBehavior: You have to set status tag for new instance of the model ';
    const STAT_STATUS_ROOT_NOT_FOUND = 'Корневой статус не найден';
    const STAT_CURRENT_STATUS_NOT_ONE_OF_CHILD_ROOT_STATUS = 'Текущий статус не принадлежит корневому статусу';
    const STAT_STATUS_IS_EMPTY = 'Идектификатор статуса не определен';
    const STAT_NEW_STATUS_TO_NOT_INSTANCEOF_STATUSES = 'Устанавливаемый статус не является наследником Statuses';
    const STAT_NEW_STATUS_ID_EMPTY_OR_NOT_INT = 'Устанавливаемый статус пуст';
    const STAT_NEW_STATUS_NOT_CHILD_BY_ROOT_STATUS = 'Устанавливаемый статус не является дочерним корневого статуса';
    const STAT_NEW_STATUS_EQUAL_OLD_STATUS = 'Устанавливаемый статус является текущим';
    const STAT_SIMPLE_LINK_RIGHT_TAG_IS_EMPTY = 'Между устанавливаемым и старым статусом отсутствует простая связь или у протстой связи отсутствует тэг доступа';
    const STAT_SIMPLE_LINK_NOT_ALLOWED = 'Нет права доступа для установки статуса';
    
}
