<?php


namespace Swoft\Queue\Annotation\Mapping;

/**
 * Class Process
 *
 * @since 2.0
 *
 * @Annotation
 * @Target("CLASS")
 * @Attributes({
 *     @Attribute("name", type="string"),
 *     @Attribute("queue", type="string"),
 * })
 */
class Job
{
    /**
     * 队列路由名称
     *
     * @var string
     */
    private $name = "*";

    /**
     * 队列归属
     *
     * @var string
     */
    private $queue = "*";


    /**
     * Process constructor.
     *
     * @param  array  $values
     */
    public function __construct(array $values)
    {
        if (isset($values['name'])) {
            $this->name = $values['name'];
        }

        if (isset($values['queue'])) {
            $this->queue = $values['queue'];
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }
}
