<?php

namespace api\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "wechat_user".
 *
 * @property integer $id
 * @property string $openid
 * @property string $nickname
 * @property integer $sex
 * @property string $headimgurl
 * @property string $country
 * @property string $province
 * @property string $city
 * @property string $access_token
 * @property string $refresh_token
 */
class WechatUser extends ActiveRecord implements IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wechat_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['openid', 'nickname', 'sex', 'headimgurl', 'access_token', 'refresh_token'], 'required'],
            [['sex'], 'integer'],
            [['nickname', 'country', 'province', 'city'], 'string', 'max' => 50],
            [['headimgurl', 'access_token', 'refresh_token'], 'string', 'max' => 255],
            [['openid'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => '编号',
            'openid' => '微信openid',
            'nickname' => '昵称',
            'sex' => '性别',
            'headimgurl' => '头像',
            'country' => '国家',
            'province' => '省份',
            'city' => '城市',
            'created_at' => '创建日期',
        ];
    }

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        //return static::findOne(['access_token' => $token]);
        return static::findOne(['openid' => $token]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

}
