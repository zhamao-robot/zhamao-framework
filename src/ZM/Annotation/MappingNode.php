<?php


namespace ZM\Annotation;


use Closure;

class MappingNode
{
    private $node;

    /** @var MappingNode[] */
    private $route = [];
    private $method = null;
    private $class = null;
    private $request_method = [];
    /** @var Closure|null */
    private $rule = null;

    public function __construct(string $node_name) { $this->node = $node_name; }

    public function addRoute(string $route_name, MappingNode $route_node) { $this->route[$route_name] = $route_node; }

    /**
     * @param string $shift
     * @return MappingNode|null
     */
    public function getRoute(string $shift) {
        return $this->route[$shift] ?? null;
    }

    public function getRealRoute(string $shift, array &$bind_params) {
        if (mb_substr(key($this->route), 0, 1) == "{" && mb_substr(key($this->route), -1, 1) == "}") {
            $param_name = mb_substr(current($this->route)->getNodeName(), 1, -1);
            $bind_params[$param_name] = $shift;
            return current($this->route);
        } else return $this->route[$shift] ?? null;
    }

    public function setMethod($method) { $this->method = $method; }

    public function setClass($class) { $this->class = $class; }

    public function setRequestMethod($method) {
        if (is_string($method)) $this->request_method = [$method];
        else $this->request_method = $method;
    }

    public function getNodeName() { return $this->node; }

    public function getRule() { return $this->rule; }

    public function setRule(Closure $rule): void { $this->rule = $rule; }

    public function removeAllRoute() { $this->route = []; }

    /**
     * @return null
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getRequestMethod(): array {
        return $this->request_method;
    }

    /**
     * @return null
     */
    public function getClass() {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getNode(): string {
        return $this->node;
    }

    public function __toString() {
        $str = "[" . $this->node . "] => ";
        if ($this->class != "" && $this->class != null)
            $str .= "\n\t" . $this->class . "->" . $this->method . ": " . implode(", ", $this->request_method);
        $str .= "\n\t[Route] => [";
        foreach ($this->route as $k => $v) {
            $r = $v;
            $r = explode("\n", $r);
            foreach ($r as $ks => $vs) {
                $r[$ks] = "\t" . $r[$ks];
            }
            $r = implode("\n", $r);
            $str .= "\n\t" . $r;
        }
        $str .= "\n]";
        return $str;
    }
}