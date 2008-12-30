<div class="note">
	<? if substr($note->name, 0, 1) == '$' ?>
		<span class="note-name note-var"><?= $note->name ?></span>
	<? else ?>
		<span class="note-name note-def"><?= $note->name ?></span>
	<? end ?>
	<span style="note-sep"> - </span><span style="note-description"><?= $note->description ?></span>
</div>
