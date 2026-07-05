<?php

class GuidanceContextExtractor extends AbstractAiContextExtractor
{
    public function extract(AiContext $context, array $data)
    {
        $this->addAiGuidance($context, $data);
    }
    protected function addAiGuidance(AiContext $context, array $data)
    {
        $context->addItem(
            'AI Guidance',
            'Purpose',
            'Use this context file as the first-pass architectural summary before reading the full profiler report.'
        );

        $context->addItem(
            'AI Guidance',
            'Magento 1/OpenMage',
            'Assume Magento 1.x/OpenMage conventions unless the detailed report proves otherwise.'
        );

        $context->addItem(
            'AI Guidance',
            'Theme work',
            'Check Theme Hierarchy and Theme Store Mapping before advising on frontend templates, layouts or CSS.'
        );
        
        $context->addItem('AI Guidance', 'Rewrite work', 'Check Rewrite Architecture and Rewrite Map before advising on model, block or helper overrides.');
        $context->addItem('AI Guidance', 'Module work', 'Check Module Architecture and Custom Modules before advising on code locations.');
        $context->addItem('AI Guidance', 'Performance work', 'Check Cache Architecture, Index Architecture and Cron Architecture before advising on frontend or catalogue performance.');
        $context->addItem('AI Guidance', 'Database work', 'Check Database Architecture before advising on setup scripts, resource versions or table-level issues.');
        $context->addItem('AI Guidance', 'EAV work', 'Check EAV Architecture and the full EAV collector output before advising on product, category, customer or customer address attributes.');
        $context->addItem('AI Guidance', 'Routing work', 'Check Router Architecture and Controller Architecture before advising on custom frontend or admin routes.');
        $context->addItem('AI Guidance', 'Layout work', 'Check Layout Architecture and Theme Resolution before advising on layout XML or template changes.');
    }
    
}
