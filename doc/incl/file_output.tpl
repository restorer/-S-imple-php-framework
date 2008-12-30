<div class="file <?= ($file->type == FileItem::Folder ? 'file-folder' : 'file-file') ?>">
	<div class="file-title"><?@h $file->name ?></div>

	<? each $file->blocks as $block ?>
		<?@ BlockOutput :block=>$block ?>
	<? end ?>

	<? each $file->childs as $child ?>
		<?@ FileOutput :file=>$child ?>
	<? end ?>
</div>
