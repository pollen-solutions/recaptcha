<?php
/**
 * @var Pollen\Field\FieldTemplateInterface $this
 */

?>
<div id="<?php echo $this->get('attrs.id') . '-intersectionObserver'; ?>">
    <?php $this->label('before'); ?>

    <?php $this->before(); ?>

    <?php if ($this->get('version') === 3) : ?>
        <input type="hidden" <?php echo $this->htmlAttrs(); ?>/>
    <?php else : ?>
        <div <?php echo $this->htmlAttrs(); ?>></div>
    <?php endif; ?>

    <?php $this->after();

    $this->label('after'); ?>
</div>