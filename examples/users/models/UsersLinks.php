<?php
/**
 * Created by PhpStorm.
 * User: dolgikh
 * Date: 28.06.16
 * Time: 16:39
 */

namespace docflow\examples\users\models;

use docflow\models\base\Link;

class UsersLinks extends Link
{
    const RELATION_TYPE_SUBORDINATION = 'Subordination';
    const RELATION_TYPE_PARTNER_PROGRAM = 'PartnerProgram';
    const RELATION_TYPE_FIRM_TREE = 'FirmTree';
    const RELATION_TYPE_REPRESENTATIVES = 'Representatives';
    const RELATION_TYPE_DEPARTMENTS = 'Departments';

    public static $_fieldNodeId = 'idx';
    public static $_fieldLinkFrom = 'from';
    public static $_fieldLinkTo = 'to';
    public static $_levelField = 'lvl';
    public static $_typeField = 'tp';
    public static $_rightTagField = '';
    public static $_relationTypeField = 'rtp';
    public static $_fieldNodeTag = 'tag';
    public static $_removedAttributes = ['id', 'atime', 'version'];

    public static function tableName()
    {
        return '{{%test_links}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['from', 'to', 'tp', 'lvl', 'rtp'], 'required', 'on' => static::LINK_TYPE_FLTREE],
            [['from', 'to', 'tp', 'rtp'], 'required', 'on' => static::LINK_TYPE_SIMPLE],
            [
                ['from', 'to', 'tp', 'rtp', 'lvl'],
                'unique',
                'targetAttribute' => ['from', 'to', 'tp', 'rtp', 'lvl'],
                'message' => 'The combination of From, To, Tp and Rtp has already been taken.'
            ],
            [['from', 'to'], 'integer'],
            [['lvl'], 'integer'],
            [['rtp'], 'string'],
            [['tp'], 'string'],
            [['from', 'to'], 'exist', 'targetClass' => Users::className(), 'targetAttribute' => 'idx'],
        ];
    }

    public function scenarios()
    {
        return array_merge(
            parent::scenarios(),
            [
                static::LINK_TYPE_FLTREE => ['from', 'to', 'tp', 'lvl', 'rtp'],
                static::LINK_TYPE_SIMPLE => ['from', 'to', 'r_tag', 'tp', 'rtp'],
            ]
        );
    }
}
