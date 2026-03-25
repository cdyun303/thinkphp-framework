<?php
declare (strict_types=1);

namespace support\base;

use think\App;
use think\exception\ValidateException;
use think\facade\View;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected \think\Request $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected App $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected bool $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected array $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 验证数据
     * @access protected
     * @param array $data 数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false): bool|array|string
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = str_contains($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 模板赋值
     * @param mixed ...$vars
     */
    protected function assign(...$vars)
    {
        View::assign(...$vars);
    }

    /**
     * 模板定位
     * @param string $template
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    protected function fetch(string $template = ''): string
    {
        //浏览器监测
        if ($this->request->browser == 'IE') {
            return View::display('
<html lang="zh-cn">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="Author" content="blog.cdyun.cc">
  <title>提示</title>
  <style type="text/css">
      html, body {margin: 0;padding: 0;height: 100%;width: 100%;background-color: #f7fafc;display: flex;flex-direction:column;justify-content: center;align-items: center;font-family: "Nunito", serif;}
      .text-box {color: #a0aec0;color: rgba(160, 174, 192, 100%);letter-spacing: .05em;font-size: 24px;padding: 20px 0;}
      .text-box span {padding-left: 1rem;padding-right: 1rem;}
      .text-box span:first-child {border-right: 1px solid #e2e8f0;}
      .text-href a{padding: 0 10px;font-size: 16px;color: #a0aec0;}
  </style>
</head>
</head>
<body>
<div class="text-box">
  <span>Error Browser</span><span>不兼容IE内核浏览器</span>
</div>
<div class="text-box text-href">
  推荐使用：
  <a href="http://www.firefox.com.cn" target="_blank">火狐浏览器</a>
  <a href="https://www.google.cn/intl/zh-CN/chrome/" target="_blank">谷歌浏览器</a>
  <a href="https://www.microsoft.com/zh-cn/edge" target="_blank">微软Edge浏览器</a>
  <a href="http://www.360.cn" target="_blank">360浏览器</a>
</div>
</body>
</html>
');
        }
        return View::fetch($template);
    }
}
