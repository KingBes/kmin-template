<?php

declare(strict_types=1);

namespace Kmin;

use \Exception;
use Throwable;
use \Dom\HTMLDocument;

/**
 * 模板引擎
 */
class Template
{
    /**
     * 模板变量
     *
     * @var array
     */
    protected array $data = [];

    /**
     * 配置参数
     *
     * @var array
     */
    protected array $config = [
        'view_path' => '',
        'cache_path' => '',
        'cache_time' => 0,
        'view_suffix' => 'php',
        'is_code' => false,
    ];

    /**
     * 模板变量替换规则
     *
     * @var array
     */
    protected array $tplVars = [
        'kmStr' => '/kmStr\(([^\)]+)\)/',
        'kmNum' => '/kmNum\(([^\)]+)\)/',
        'kmVar' => '/kmVar\(([^\)]+)\)/',
        'kmBool' => '/kmBool\(([^\)]+)\)/',
        'kmJson' => '/kmJson\(([^\)]+)\)/',
        'kmInclude' => '/kmInclude\(([^\)]+)\)/',
    ];

    /**
     * 构造函数
     *
     * @param array $config 配置参数
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        // 确保路径以目录分隔符结尾
        foreach (['view_path', 'cache_path'] as $path) {
            if (!empty($this->config[$path]) && substr($this->config[$path], -1) !== DIRECTORY_SEPARATOR) {
                $this->config[$path] .= DIRECTORY_SEPARATOR;
            }
        }

        // 创建缓存目录（如果不存在）
        if (!empty($this->config['cache_path']) && !is_dir($this->config['cache_path'])) {
            mkdir($this->config['cache_path'], 0755, true);
        }
    }

    /**
     * 赋值
     *
     * @param array $vars 变量数组
     * @return static
     */
    public function assign(array $vars = []): static
    {
        $this->data = array_merge($this->data, $vars);
        return $this;
    }

    /**
     * 解析模板变量
     *
     * @param string $tpl 模板内容
     * @return string 解析后的内容
     */
    protected function parseVars(string $tpl): string
    {
        foreach ($this->tplVars as $key => $pattern) {
            $tpl = preg_replace_callback($pattern, [$this, $key], $tpl);
        }
        return $tpl;
    }

    /**
     * 解析模板
     *
     * @param string $tpl
     * @return string
     */
    protected function parseTpl(string $tpl): string
    {
        $dom = HTMLDocument::createFromString($tpl, LIBXML_NOERROR);
        $js = $dom->querySelector('script')->innerHTML;
        return $js;
    }

    /**
     * 解析入口模板
     *
     * @param string $content 模板内容
     * @return string 解析后的内容
     */
    protected function parseMain(string $content): string
    {
        $mainFile = $this->config['view_path'] . 'main.' . $this->config['view_suffix'];
        if (file_exists($mainFile)) {
            $mainContent = file_get_contents($this->config['view_path'] . 'main.' . $this->config['view_suffix']);
            $content = str_replace('kmContent()', $content, $mainContent);
        }
        return $content;
    }

    /**
     * 渲染模板
     *
     * @param string $template 模板文件名
     * @param array $vars 模板变量
     * @return string
     */
    public function fetch(string $template, array $vars = []): string
    {
        if ($vars) {
            $this->data = array_merge($this->data, $vars);
        }
        // 模板文件路径
        $tplFile  = $this->config['view_path'] . $template . '.' . $this->config['view_suffix'];

        if (!file_exists($tplFile)) {
            throw new Exception('The template file does not exist:' . $tplFile);
        }
        // 缓存文件路径
        $cacheFile = $this->config['cache_path'] . md5($template) . '.php';
        // 检查是否需要重新编译模板
        if (!$this->isCacheValid($cacheFile, $tplFile)) {
            $content = file_get_contents($tplFile);
            if ($this->config['is_code']) { // 如果是代码模板，需要解析模板
                $content = $this->parseTpl($content);
            } else {
                // 模板布局
                $content = $this->parseMain($content);
            }
            $compiled = $this->parseVars($content);
            file_put_contents($cacheFile, $compiled);
        }
        extract($this->data, EXTR_SKIP);
        ob_start();
        try {
            include $cacheFile;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }

    protected function kmStr($matches)
    {
        return "'<?php echo htmlspecialchars({$matches[1]}, ENT_QUOTES, 'UTF-8'); ?>'";
    }

    protected function kmNum($matches)
    {
        return "<?php echo (float){$matches[1]}; ?>";
    }

    protected function kmVar($matches)
    {
        return "<?php echo htmlspecialchars({$matches[1]}, ENT_QUOTES, 'UTF-8'); ?>";
    }

    protected function kmBool($matches)
    {
        return "<?php echo {$matches[1]} ? 'true' : 'false'; ?>";
    }

    protected function kmJson($matches)
    {
        return "JSON.parse(<?php echo json_encode({$matches[1]}, JSON_UNESCAPED_UNICODE); ?>)";
    }

    protected function kmInclude($matches)
    {
        $file = $this->config['view_path'] . $matches[1] . '.' . $this->config['view_suffix'];
        if (!file_exists($file)) {
            throw new Exception('The template file does not exist:' . $file);
        }
        return "<?php include '{$file}'; ?>";
    }

    /**
     * 检查目录是否相等
     *
     * @param string $dir1 目录1
     * @param string $dir2 目录2
     * @return boolean
     */
    protected function isEqDir(string $dir1, string $dir2): bool
    {
        $dir1 = $this->fileReplace($dir1);
        $dir2 = $this->fileReplace($dir2);
        if (str_ends_with($dir1, DIRECTORY_SEPARATOR)) {
            // 删除最后一个目录分隔符
            $dir1 = rtrim($dir1, DIRECTORY_SEPARATOR);
        }
        if (str_ends_with($dir2, DIRECTORY_SEPARATOR)) {
            // 删除最后一个目录分隔符
            $dir2 = rtrim($dir2, DIRECTORY_SEPARATOR);
        }
        return $dir1 === $dir2;
    }

    /**
     * 替换文件路径中的分隔符
     *
     * @param string $path 文件路径
     * @return string
     */
    protected function fileReplace(string $path): string
    {
        return str_replace(
            ['\\', '/', '\\\\', '//'],
            DIRECTORY_SEPARATOR,
            $path
        );
    }

    /**
     * 检查缓存是否有效
     *
     * @param string $cacheFile 缓存文件路径
     * @param string $tplFile 模板文件路径
     * @return bool
     */
    protected function isCacheValid(string $cacheFile, string $tplFile): bool
    {

        // 缓存文件不存在
        if (!file_exists($cacheFile)) {
            return false;
        }

        // 检查模板文件是否被修改
        if (filemtime($tplFile) > filemtime($cacheFile)) {
            return false;
        }

        // 判断入口模板是否存在
        $mainFile = $this->config['view_path'] . 'main.' . $this->config['view_suffix'];
        if (file_exists($mainFile)) {
            // 检查入口模板是否被修改
            if (filemtime($mainFile) > filemtime($cacheFile)) {
                return false;
            }
        }

        // 检查缓存是否过期
        if (
            $this->config['cache_time'] > 0
            && time() - filemtime($cacheFile) > $this->config['cache_time']
        ) {
            return false;
        }
        return true;
    }
}
