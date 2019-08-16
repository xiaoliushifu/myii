##配置
1. config/main.php(main-local.php)中增加模块，配置样例如下：

```php
[
	……
	//配置module
	'modules' => [
		'rbac' => [
			// 类名称	
			'class' => \Wkii\Yii2Rbac\Rbac::className(),
			// 用户model类
			'userClass' => 'sys\models\user\User',
			// 平台ID，需要配置该参数，来标注是那个平台
			"platformId" => 1,
			// 是否是debug状态
            'debug' => true,
          // 这是一个排出的属性，在这个属性中的module，controller或者action都将不会进行扫描，以及权限的判断
            'except' => ['module' => ['gii', 'debug', 'rbac'], 'controller' => [], 'action' => []],
		]
	],
	//同时需要配置authManager
	'authManager' => [
		'class' => \Wkii\Yii2Rbac\DbManager::className(),
		// 默认如果是rbac，则不需要配置该参数
		'rbacModuleName' => 'newRbac' 
	]
	……
]
```
2. 控制器中行为的配置

```php
……
class Controller extends \yii\web\Controller
{
	public function behaviors()
	{
		return [
			'rbac' => [
				'class' => RbacBehavior::className(),
				// 可配，展示权限不足时的提示
				'errorContent' => '……', 
				// 可配，权限不足时展示的view视图，优先级高于errorContent
				'errorView' => '……', 
			]
		];
	}
}
……
```

**注意**

`userClass`只是在角色和用户之间的关联的时候，才会使用到，例如，用户与角色进行绑定，角色下拥有用户信息展示等，都将**可能**需要userClass所对应的model类的协助

##用户与角色
当授权角色管理页面打开时，每一个角色都有`已关联用户`和`关联用户`两项操作，该操作需要展示用户的列表信息，所以单在该模块无法完成，如果不打算使用这两个操作，可以忽略以下使用方法

**使用方法:**
因为这两个列表均使用了Yii2自带的组件`GridView`,需要在module模块的参数`userClass`中增加方法：
`getRoleRelatedUserColumn()`, 该方法返回的其实就是`GridView`中`columns`,而 `GridView`中`dataProvider`参数查询的时候`with('user')`, 所以如下：

```php
……
public function getRoleRelatedUserColumn()
{
	return [
		'role_id', // UserRole 类中的属性
		'user.user_id', // userClass中的属性,
		[
			'attribute' => 'user.status',
			'value' => function ($model, ……) {
				return ……;
			}
		]
	];
}
……
```


##对外提供
因该模块需要与用户进行绑定，除此之外，都将在模块本身进行操作，因此，提供如下方法：

```php
// 查询所有角色，其中attributes参数如下：['status' => 0, 'role_name' => '管理员']
Wkii\Yii2Rbac\models\AuthRole::search($attributes, $pageSize = 15);
通过传入用户角色的ID,获取ID的名称, 该参数可以是一个ID也可以是个ID的数组,返回值['role_id' => 'role_name', 'role_id' => 'role_name'] 
Wkii\Yii2Rbac\models\AuthRole::getRolesByIds($ids);
// 批量添加某用户与角色之间的关联
Wkii\Yii2Rbac\models\UserRole::batchInsertRole($user_id, $roleIds);
// 批量为某用户删除角色关系
Wkii\Yii2Rbac\models\UserRole::batchDelete($user_id, $roleIds);
// 查询角色对应的角色，返回值['role_id' => 'role_name', 'role_id' => 'role_name']
Wkii\Yii2Rbac\models\UserRole::getRolesByUserId($user_id);

// 更新某用户缓存授权项目
Yii::$app->authManager->revokeAll($model->user_id);
```