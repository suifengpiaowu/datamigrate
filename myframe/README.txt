目录结构：
|--application – 存放程序代码
|--config – 存放程序配置
|--db – 存放数据库备份内容
|--library – 存放框架代码
|--public – 存放静态文件 
|--scripts – 存放命令行工具
|--tmp – 存放临时数据 

命名规范：
1. MySQL的表名需小写并采用复数形式，如items,cars
2. 模块名（Models）需首字母大写，并采用单数模式，如Item,Car
3. 控制器（Controllers）需首字母大写，采用复数形式并在名称中添加“Controller”，如ItemsController, CarsController
4. 视图（Views）采用复数形式，并在后面添加行为作为文件，如：items/view.php, cars/buy.php

nginx配置：
if ( !-e $request_filename ){
    rewrite ^/(.*)   /index.php?url=$1 last;
}

apache配置：
1. apache服务器需设置 RewriteEngine on
2. 在public目录放入.htaccess文件内容如下
	<IfModule mod_rewrite.c>
	   RewriteEngine on
	   #如果文件存在就直接访问目录不进行RewriteRule
	   RewriteCond %{REQUEST_FILENAME} !-f
	   #如果目录存在就直接访问目录不进行RewriteRule
	   RewriteCond %{REQUEST_FILENAME} !-d
	   #将所有其他URL重写到 index.php/URL
	   RewriteRule ^(.*)$ index.php?url=$1 [PT,L]
	</IfModule>