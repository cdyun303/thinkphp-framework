# Thinkphp8.0项目多应用结构开发脚手架

> Thinkphp8.0项目多应用结构开发脚手架是一个基于Thinkphp8.0框架的快速开发模板，专为数据处理类项目设计。它提供了一套完整的项目结构和常用功能，帮助开发者快速构建高性能的PHP应用。

## 快速开始

### 安装步骤

1. **安装项目**

```bash
composer create-project cdyun/thinkphp-framework thinkphp
```

2. **安装依赖**

```bash
cd thinkphp

composer update
```

3. **安装常用组件（可选）**

```bash
# 请求响应Response扩展、支持加密解密（已安装）
composer require cdyun/thinkphp-response

# 文件上传Upload扩展（已安装）
composer require cdyun/thinkphp-upload

# 缓存Cache扩展（已安装）
composer require cdyun/thinkphp-cache

# Swagger扩展
composer require cdyun/thinkphp-swagger

# PHP8.1+ 通用工具包
composer require cdyun/php-tool

# 视图
composer require topthink/think-view

# 验证码
composer require topthink/think-captcha
```

4. **配置环境**

复制 `.env.example` 文件为 `.env` 并修改配置：

```bash
# 编辑 .env 文件，设置数据库连接等信息
cp .env.example .env
```

### 环境要求

- PHP >= 8.0.0
- Composer >= 2.0

## 目录结构

```
thinkphp/                                 部署目录
├── app                                   应用目录
│   ├── v1                                V1应用
│   │   ├── controller                    控制器
│   │   ├── model                         模型
│   │   ├── route                         路由
│   │   ├── view                          视图
│   │   └── ...                           其他
│   │
│   ├── v2                                V2应用
│   │   ├── controller                    控制器
│   │   ├── model                         模型
│   │   ├── route                         路由
│   │   ├── view                          视图
│   │   └── ...                           其他
│   │
│   ├── common.php                        全局公共函数文件
│   ├── event.php                         全局事件定义文件
│   ├── middleware.php                    全局中间件定义文件
│   ├── provider.php                      服务提供器配置
│   └── service.php                       系统服务定义文件
│
├── config                                全局配置目录
│   ├── app.php                           应用配置
│   ├── cache.php                         缓存配置
│   ├── cdyun.php                         cdyun扩展插件配置
│   ├── console.php                       控制台配置
│   ├── cookie.php                        Cookie配置
│   ├── database.php                      数据库配置
│   ├── filesystem.php                    文件系统配置
│   ├── lang.php                          多语言配置
│   ├── log.php                           日志配置
│   ├── middleware.php                    中间件配置
│   ├── route.php                         URL和路由配置
│   ├── session.php                       Session配置
│   ├── swagger.php                       Swagger配置（Swagger扩展）
│   ├── trace.php                         Trace配置
│   └── view.php                          视图配置
│
├── public                                静态资源目录
│  ├─index.php                            入口文件
│  ├─router.php                           快速测试文件
│  └─.htaccess                            用于apache的重写
│
├── runtime                               应用的运行时目录，需要可写权限
├── vendor                                composer安装的第三方类库目录
├── support                               全局类库目录
│   ├── base                              基础类目录
│   │   ├── BaseController.php            基础控制器类
│   │   ├── BaseModel.php                 基础模型类
│   │   └── BaseValidate.php              基础验证类
│   │
│   ├── exception                         异常类目录
│   │   └── AppException.php              自定义异常类
│   │  
│   ├── middleware                        中间件类目录
│   │   └── BrowseCheckMiddleware.php     浏览器类型中间件
│   │  
│   ├── listener                          事件监听类目录
│   │  
│   ├── ...                               其他全局类库目录
│   │  
│   ├── AppService.php                    应用服务类文件
│   ├── ExceptionHandle.php               异常处理类文件
│   └── Request.php                       请求类文件
│
├── .env.example                          环境变量示例文件
├── LICENSE                               MIT开源协议文件
├── README.md                             README.md
├── composer.json                         项目依赖配置文件
└── think.php                             命令行入口文件
```

## 许可证

MIT License
