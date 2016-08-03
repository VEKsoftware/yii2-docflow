<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 28.06.16
 * Time: 16:39
 */

namespace docflow\examples\users\models;

use docflow\behaviors\LinkOrderedBehavior;
use docflow\behaviors\LinkSimpleBehavior;
use docflow\behaviors\LinkStructuredBehavior;
use docflow\behaviors\StatusBehavior;
use docflow\models\Document;
use yii;
use yii\db\ActiveQuery;

class Users extends Document
{
    public static function tableName()
    {
        return '{{%test_users}}';
    }

    /**
     * This function returns the document tag. This tag is used to get
     * all information about the doument type from the database.
     *
     * @return string Document tag
     */
    public static function docTag()
    {
        return 'vid';
    }

    /**
     * This function returns the structure containing access rights tags.
     *
     * @return mixed Structure is the following
     *  [
     *     [
     *        'operation' => 'view', // This is the name of operation. It will be refered in the access check methods like $user->can(operation)
     *        'label' => 'View document',
     *        'conditions' => [ // These conditions are handled in the document model and are set up in the access settings page
     *            [
     *                'condition' => 'own',
     *                'label' => 'Only my',
     *            ],
     *            [
     *                'condition' => 'all',
     *                'label' => 'All',
     *            ],
     *        ],
     *    ],
     *    [
     *      ...
     *    ],
     *    ...
     *  ],
     */
    public static function accessData()
    {
        return [];
    }

    public function behaviors()
    {
        return [
            'statuses' => [
                'class' => StatusBehavior::className(),
                'statusIdField' => 'status_id',
                'statusRootTag' => '1'
            ],
            'firmTreeAllS' => [
                'class' => LinkSimpleBehavior::className(),
                'linkClass' => UsersLinksFirmTreeSimple::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'firmTree\'::text)::int',
                'orderedFieldValue' => 'orderFirmTree',
                'indexBy' => 'tag'
            ],
            'departments' => [
                'class' => LinkSimpleBehavior::className(),
                'linkClass' => UsersLinksDepartments::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    $query->andWhere(['user_type_id' => 1]);
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'departments\'::text)::int',
                'orderedFieldValue' => 'orderDepartments',
                'indexBy' => 'tag'
            ],
            'representatives' => [
                'class' => LinkSimpleBehavior::className(),
                'linkClass' => UsersLinksRepresentatives::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    $query->andWhere(['user_type_id' => 2]);
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'representatives\'::text)::int',
                'orderedFieldValue' => 'orderRepresentatives',
                'indexBy' => 'tag'
            ],
            'firmTree' => [
                'class' => LinkStructuredBehavior::className(),
                'linkClass' => UsersLinksFirmTreeFlTree::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'firmTree\'::text)::int',
                'orderedFieldValue' => 'orderFirmTree',
                'indexBy' => 'tag'
            ],
            'partnerProgram' => [
                'class' => LinkStructuredBehavior::className(),
                'linkClass' => UsersLinksPartnerProgram::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    $query->andWhere(['user_type_id' => 2]);
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'partnerProgram\'::text)::int',
                'orderedFieldValue' => 'orderPartnerProgram',
                'indexBy' => 'tag'
            ],
            'subordination' => [
                'class' => LinkStructuredBehavior::className(),
                'linkClass' => UsersLinksSubordination::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    $query->andWhere(['user_type_id' => 3]);
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'subordination\'::text)::int',
                'orderedFieldValue' => 'orderSubordination',
                'indexBy' => 'tag'
            ],
            'firmTreeAllO' => [
                'class' => LinkOrderedBehavior::className(),
                'linkClass' => UsersLinksFirmTreeFlTree::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'firmTree\'::text)::int',
                'orderedFieldValue' => 'orderFirmTree',
                'indexBy' => 'tag'
            ],
            'firmTreeOrdered' => [
                'class' => LinkOrderedBehavior::className(),
                'linkClass' => UsersLinksFirmTreeFlTree::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    $query->andWhere(['user_type_id' => 1]);
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'firmTree\'::text)::int',
                'orderedFieldValue' => 'orderFirmTree',
                'indexBy' => 'tag'
            ],
            'partnerProgramOrdered' => [
                'class' => LinkOrderedBehavior::className(),
                'linkClass' => UsersLinksPartnerProgram::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    $query->andWhere(['user_type_id' => 2]);
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'partnerProgram\'::text)::int',
                'orderedFieldValue' => 'orderPartnerProgram',
                'indexBy' => 'tag'
            ],
            'subordinationOrdered' => [
                'class' => LinkOrderedBehavior::className(),
                'linkClass' => UsersLinksSubordination::className(),
                'documentQuery' => function (ActiveQuery $query) {
                    $query->andWhere(['user_type_id' => 3]);
                    /* True - конечный результат будет All(); null, false - one() */
                    $query->multiple = true;

                    return $query;
                },
                'orderedFieldDb' => '("order_idx"->>\'subordination\'::text)::int',
                'orderedFieldValue' => 'orderSubordination',
                'indexBy' => 'tag'
            ],
        ];
    }

    /**
     * Получаем пользователя по короткому имени
     *
     * @param string $shortName - короткое имя
     *
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getUsersByShortName($shortName)
    {
        return static::find()
            ->where(['=', 'short_name', $shortName])
            ->limit(1)
            ->one();
    }

    public function isAllowed()
    {
        return true;
    }

    /**
     * Return field name which use how Document `name`
     *
     * @return string Document name
     */
    public function getDocName()
    {
        return $this->{'short_name'};
    }

    /**
     * Получаем документ по его дентификатору
     *
     * @param int $nodeId - id ноды
     *
     * @return ActiveQuery
     */
    public static function getDocumentByNodeId($nodeId)
    {
        return static::find()->where(['idx' => $nodeId]);
    }

    /**
     * Аттрибуты содержащие json
     *
     * @return array
     */
    public static function jsonBFields()
    {
        return ['order_idx'];
    }

    /**
     * Гетер, для сортировки по main
     *
     * @return mixed
     */
    public function getOrderMain()
    {
        return $this->order_idx->main;
    }

    /**
     * Гетер, для сортировки по firmTree
     *
     * @return mixed
     */
    public function getOrderFirmTree()
    {
        return $this->order_idx->firmTree;
    }

    /**
     * Сетер для сортировки по firmTree
     *
     * @param mixed $value - входящее значение
     *
     * @return void
     */
    public function setOrderFirmTree($value)
    {
        $this->order_idx->firmTree = $value;
    }
}
