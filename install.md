Composer:
"repositories": [
    ....
    {
      "type": "git",
      "url": "git@github.com:VEKsoftware/yii2-docflow.git"
    }
],
"require": {
    ......
    "VEKsoftware/yii2-docflow": "dev-master"
},


Дописать в config
console/config/main.php:
'bootstrap' => [
    ...
    'docflow',
],
'modules' => [
    .....
    docflow' => [
       'class' => 'docflow\Docflow',
       'accessClass' => 'common\models\Acess',
       'db' => 'db'
    ],
],


Дописать в config
frontend/config/main.php:
'bootstrap' => [
    ...
    'docflow',
],
'modules' => [
    .....
    docflow' => [
       'class' => 'docflow\Docflow',
       'accessClass' => 'common\models\Acess',
       'db' => 'db'
    ],
],


Создать:
common\models\Acess.php:

namespace common\models;

use yii\base\Behavior;

class Acess extends Behavior
{
    public function isAllowed($operation, $relation = null, $user = null)
    {
        return true;
    }
}


####Миграции:
php yii migrate/up
php yii migrate/up --migrationPath=@docflow/migrations
php yii migrate/up --migrationPath=@docflow/examples/users/migrations
php yii migrate/up --migrationPath=@docflow/examples/jsonb/migrations

php yii migrate/down 1 --migrationPath=@docflow/examples/jsonb/migrations
php yii migrate/down 1 --migrationPath=@docflow/examples/users/migrations
php yii migrate/down 1 --migrationPath=@docflow/migrations
php yii migrate/down