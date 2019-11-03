<?php


namespace Swoft\Queue\Annotation\Parser;

use Swoft\Annotation\Annotation\Mapping\AnnotationParser;
use Swoft\Annotation\Annotation\Parser\Parser;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Queue\Annotation\Mapping\Job;
use Swoft\Queue\Manager\JobsManager;

/**
 * Class ProcessParser
 *
 * @since 2.0
 *
 * @AnnotationParser(annotation=Job::class)
 */
class JobParser extends Parser
{
    /**
     * Parse object
     *
     * @param  int  $type  Class or Method or Property
     * @param  Job  $annotationObject  Annotation object
     *
     * @return array
     */
    public function parse(int $type, $annotationObject): array
    {
        JobsManager::registeredJob($annotationObject,$this->className);

        return [$this->className, $this->className, Bean::SINGLETON, ''];
    }
}
