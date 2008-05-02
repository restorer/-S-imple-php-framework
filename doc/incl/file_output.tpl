<div class="file <?= ($file->type == FILEITEM_FOLDER ? 'file-folder' : 'file-file') ?>">
	<div class="file-title"><?# $file->name ?></div>

	<? each $file->blocks() as $block ?>
		<?@ BlockOutput :block=>$block ?>
	<? end ?>

	<? each $file->childs() as $child ?>
		<?@ FileOutput :file=>$child ?>
	<? end ?>
</div>
