<?php
/*
 * This file is part of the FreshDoctrineEnumBundle
 *
 * (c) Artem Genvald <genvaldartem@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fresh\DoctrineEnumBundle\DependencyInjection\Compiler;

use App\Entity\User\ApplicationUser;
use Fresh\DoctrineEnumBundle\DBAL\Types\WorkflowInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\SupportStrategy\ClassInstanceSupportStrategy;
use Symfony\Component\Workflow;

/**
 * WorkflowPass.
 */
class WorkflowPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('workflow.registry')) {
            return;
        }

        $workflowRegistry = $container->getDefinition('workflow.registry');

        $registeredTypes = $container->getParameter('doctrine.dbal.connection_factory.types');
        foreach ($registeredTypes as $details) {
            $fqcn = $details['class'];

            /** @var $fqcn \Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType */
            if (is_subclass_of($fqcn, WorkflowInterface::class)) {
//                $fqcn::getChoices();

                $strategyDefinition = new Definition(ClassInstanceSupportStrategy::class, [ApplicationUser::class]);
                $strategyDefinition->setPublic(false);
//                $registryDefinition->addMethodCall('add', array(new Reference($workflowId), $strategyDefinition));

                $t = $this->processEnumWorkflowDefinition($fqcn);
                $container->setDefinition('workflow.user', $t);

                $workflowRegistry->addMethodCall('add', [new Reference('workflow.user'), $strategyDefinition]);

            };
        }

        /** @var $enumTypeClass */

        return;
    }

    /**
     * @param \Fresh\DoctrineEnumBundle\DBAL\Types\AbstractEnumType $fqcn
     *
     * @return Workflow\Workflow
     */
    private function processEnumWorkflowDefinition($fqcn)
    {
        $transitions = [];
        $transitions[] = new Definition(Workflow\Transition::class, ['become_tourist', 'none', 'none']);
        $transitions[] = new Definition(Workflow\Transition::class, ['become_guide', 'none', 'guide']);
        $transitions[] = new Definition(Workflow\Transition::class, ['switch_to_tourist', 'guide', 'tourist']);
        $transitions[] = new Definition(Workflow\Transition::class, ['switch_to_guide', 'tourist', 'guide']);

        $definition = new Definition(Workflow\Definition::class);
        $definition->setPublic(false);
        $definition->addArgument(['none', 'tourist', 'guide']);
        $definition->addArgument($transitions);
//        $definitionDefinition->addTag('workflow.definition', array(
//            'name' => $name,
//            'type' => $type,
//            'marking_store' => isset($workflow['marking_store']['type']) ? $workflow['marking_store']['type'] : null,
//        ));
//        if (isset($workflow['initial_place'])) {
//            $definitionDefinition->addArgument($workflow['initial_place']);
//        }

//        $definitionBuilder = new DefinitionBuilder();
//
//        $definition = $definitionBuilder
//            ->addPlaces(['draft', 'review', 'rejected', 'published'])
//            ->addTransition(new Transition('to_review', 'draft', 'review'))
//            ->addTransition(new Transition('publish', 'review', 'published'))
//            ->addTransition(new Transition('reject', 'review', 'rejected'))
//            ->build();

//        $marking = new SingleStateMarkingStore('currentState');
//        $workflow = new Workflow\Workflow($definition, $marking, null, 'qwerty');

        $marking = new Definition(SingleStateMarkingStore::class, ['activeModeType']);
        $workflow = new Definition(Workflow\Workflow::class, [
            $definition, $marking, null, 'user'
        ]);

        return $workflow;
    }
}
