<div class="block <?= count($block->childs()) ? 'block-has-childs' : '' ?>">
	<? if strlen($block->title) ?>
		<div class="block-title"><?= $block->title ?></div>
	<? end ?>

	<? each $block->notes() as $note ?>
		<?@ NoteOutput :note=>$note ?>
	<? end ?>

	<? if (strlen($block->title) || count($block->notes())) && strlen($block->text) ?>
		<div class="block-sep">&nbsp;</div>
	<? end ?>

	<? if strlen($block->text) ?>
		<div class="block-text"><?= nl2br($block->text) ?></div>
	<? end ?>

	<? if strlen($block->example) ?>
		<div class="block-example">
			<div class="block-example-header">Example:</div>
			<?= nl2br($block->example) ?>
		</div>
	<? end ?>

	<? each $block->childs() as $child ?>
		<?@ BlockOutput :block=>$child ?>
	<? end ?>
</div>
