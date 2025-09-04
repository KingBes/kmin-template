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
        'view_suffix' => 'html',
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
        $tpl = $dom->querySelector('template')->innerHTML;
        $css = $dom->querySelector('style')->innerHTML;
        $js = $dom->querySelector('script')->innerHTML;
        // 匹配 "class extends KMin {"
        $js = preg_replace('/\s*class\s+extends\s+KMin\s+{/', "
        class extends KMin {
            css() {
                return `{$css}`;
            }
            render() {
                return `{$tpl}`;
            }
        ", $js);

        $mainFile = $this->config['view_path'] . 'main.' . $this->config['view_suffix'];
        if (file_exists($mainFile)) {
            preg_match('/\s*customElements.define\([\'"]([\w-]+)[\'"],/', $js, $matches);
            if (!isset($matches[1])) {
                throw new Exception('The template file does not define a custom element.');
            }
            $TagName = $matches[1];
            $mainDom = HTMLDocument::createFromString(file_get_contents($mainFile));
            $mainDom->querySelector("body")->innerHTML = "<{$TagName}></{$TagName}><script type=\"module\">{$js}</script>";
            return $mainDom->saveHTML();
        }
        return $js;
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
            $content = $this->parseTpl($content);
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

        // 检查main文件是否被修改
        $mainFile = $this->config['view_path'] . 'main.' . $this->config['view_suffix'];
        if (file_exists($mainFile) && filemtime($mainFile) > filemtime($cacheFile)) {
            return false;
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
