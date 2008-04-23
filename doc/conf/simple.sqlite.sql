CREATE TABLE "file_items" (
	"id" INTEGER PRIMARY KEY,
	"parent_id" INTEGER NOT NULL DEFAULT 0,
	"name" VARCHAR(255),
	"type" INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE "block_items" (
	"id" INTEGER PRIMARY KEY,
	"parent_id" INTEGER NOT NULL DEFAULT 0,
	"file_id" INTEGER NOT NULL DEFAULT 0,
	"title" TEXT,
	"text" TEXT,
	"example" TEXT
);

CREATE TABLE "note_items" (
	"id" INTEGER PRIMARY KEY,
	"block_id" INTEGER NOT NULL DEFAULT 0,
	"name" VARCHAR(255),
	"description" VARCHAR(255)
);
