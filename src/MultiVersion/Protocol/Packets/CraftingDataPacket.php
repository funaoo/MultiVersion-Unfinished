<?php

declare(strict_types=1);

namespace MultiVersion\Protocol\Packets;

use MultiVersion\Protocol\Packets\BasePacket;
use MultiVersion\Utils\BinaryStream;

final class CraftingDataPacket extends BasePacket {

    public const ENTRY_SHAPELESS = 0;
    public const ENTRY_SHAPED = 1;
    public const ENTRY_FURNACE = 2;
    public const ENTRY_FURNACE_DATA = 3;
    public const ENTRY_MULTI = 4;
    public const ENTRY_SHULKER_BOX = 5;
    public const ENTRY_SHAPELESS_CHEMISTRY = 6;
    public const ENTRY_SHAPED_CHEMISTRY = 7;
    public const ENTRY_SMITHING_TRANSFORM = 8;

    public array $entries = [];
    public array $potionTypeRecipes = [];
    public array $potionContainerRecipes = [];
    public array $materialReducerRecipes = [];
    public bool $cleanRecipes;

    public function __construct() {
        $this->packetId = 0x34;
    }

    public function encode(): void {
        $stream = $this->getBuffer();
        $this->encodeHeader($stream);

        $stream->putUnsignedVarInt(count($this->entries));
        foreach ($this->entries as $entry) {
            $this->encodeRecipe($stream, $entry);
        }

        $this->encodePotionTypeRecipes($stream);
        $this->encodePotionContainerRecipes($stream);
        $this->encodeMaterialReducerRecipes($stream);
        $stream->putBool($this->cleanRecipes);

        $this->encoded = true;
    }

    public function decode(): void {
        $stream = $this->getBuffer();
        $this->decodeHeader($stream);

        $count = $stream->getUnsignedVarInt();
        $this->entries = [];
        for ($i = 0; $i < $count; $i++) {
            $this->entries[] = $this->decodeRecipe($stream);
        }

        $this->decodePotionTypeRecipes($stream);
        $this->decodePotionContainerRecipes($stream);
        $this->decodeMaterialReducerRecipes($stream);
        $this->cleanRecipes = $stream->getBool();

        $this->decoded = true;
    }

    private function encodeRecipe(BinaryStream $stream, array $recipe): void {
        $stream->putVarInt($recipe['type']);

        match($recipe['type']) {
            self::ENTRY_SHAPELESS, self::ENTRY_SHAPELESS_CHEMISTRY => $this->encodeShapelessRecipe($stream, $recipe),
            self::ENTRY_SHAPED, self::ENTRY_SHAPED_CHEMISTRY => $this->encodeShapedRecipe($stream, $recipe),
            self::ENTRY_FURNACE, self::ENTRY_FURNACE_DATA => $this->encodeFurnaceRecipe($stream, $recipe),
            self::ENTRY_MULTI => $this->encodeMultiRecipe($stream, $recipe),
            self::ENTRY_SHULKER_BOX => $this->encodeShulkerBoxRecipe($stream, $recipe),
            self::ENTRY_SMITHING_TRANSFORM => $this->encodeSmithingTransformRecipe($stream, $recipe),
            default => null
        };
    }

    private function decodeRecipe(BinaryStream $stream): array {
        $recipe = [];
        $recipe['type'] = $stream->getVarInt();

        match($recipe['type']) {
            self::ENTRY_SHAPELESS, self::ENTRY_SHAPELESS_CHEMISTRY => $recipe = $this->decodeShapelessRecipe($stream, $recipe),
            self::ENTRY_SHAPED, self::ENTRY_SHAPED_CHEMISTRY => $recipe = $this->decodeShapedRecipe($stream, $recipe),
            self::ENTRY_FURNACE, self::ENTRY_FURNACE_DATA => $recipe = $this->decodeFurnaceRecipe($stream, $recipe),
            self::ENTRY_MULTI => $recipe = $this->decodeMultiRecipe($stream, $recipe),
            self::ENTRY_SHULKER_BOX => $recipe = $this->decodeShulkerBoxRecipe($stream, $recipe),
            self::ENTRY_SMITHING_TRANSFORM => $recipe = $this->decodeSmithingTransformRecipe($stream, $recipe),
            default => null
        };

        return $recipe;
    }

    private function encodeShapelessRecipe(BinaryStream $stream, array $recipe): void {
        $stream->putString($recipe['recipeId']);

        $stream->putUnsignedVarInt(count($recipe['input']));
        foreach ($recipe['input'] as $item) {
            $this->encodeRecipeIngredient($stream, $item);
        }

        $stream->putUnsignedVarInt(count($recipe['output']));
        foreach ($recipe['output'] as $item) {
            $this->encodeItemStack($stream, $item);
        }

        $stream->putUUID($recipe['uuid']);
        $stream->putString($recipe['craftingTag']);
        $stream->putVarInt($recipe['priority']);
        $stream->putUnsignedVarInt($recipe['networkId'] ?? 0);
    }

    private function decodeShapelessRecipe(BinaryStream $stream, array $recipe): array {
        $recipe['recipeId'] = $stream->getString();

        $inputCount = $stream->getUnsignedVarInt();
        $recipe['input'] = [];
        for ($i = 0; $i < $inputCount; $i++) {
            $recipe['input'][] = $this->decodeRecipeIngredient($stream);
        }

        $outputCount = $stream->getUnsignedVarInt();
        $recipe['output'] = [];
        for ($i = 0; $i < $outputCount; $i++) {
            $recipe['output'][] = $this->decodeItemStack($stream);
        }

        $recipe['uuid'] = $stream->getUUID();
        $recipe['craftingTag'] = $stream->getString();
        $recipe['priority'] = $stream->getVarInt();
        $recipe['networkId'] = $stream->getUnsignedVarInt();

        return $recipe;
    }

    private function encodeShapedRecipe(BinaryStream $stream, array $recipe): void {
        $stream->putString($recipe['recipeId']);
        $stream->putVarInt($recipe['width']);
        $stream->putVarInt($recipe['height']);

        for ($i = 0; $i < $recipe['width'] * $recipe['height']; $i++) {
            $this->encodeRecipeIngredient($stream, $recipe['input'][$i] ?? null);
        }

        $stream->putUnsignedVarInt(count($recipe['output']));
        foreach ($recipe['output'] as $item) {
            $this->encodeItemStack($stream, $item);
        }

        $stream->putUUID($recipe['uuid']);
        $stream->putString($recipe['craftingTag']);
        $stream->putVarInt($recipe['priority']);
        $stream->putUnsignedVarInt($recipe['networkId'] ?? 0);
    }

    private function decodeShapedRecipe(BinaryStream $stream, array $recipe): array {
        $recipe['recipeId'] = $stream->getString();
        $recipe['width'] = $stream->getVarInt();
        $recipe['height'] = $stream->getVarInt();

        $recipe['input'] = [];
        for ($i = 0; $i < $recipe['width'] * $recipe['height']; $i++) {
            $recipe['input'][] = $this->decodeRecipeIngredient($stream);
        }

        $outputCount = $stream->getUnsignedVarInt();
        $recipe['output'] = [];
        for ($i = 0; $i < $outputCount; $i++) {
            $recipe['output'][] = $this->decodeItemStack($stream);
        }

        $recipe['uuid'] = $stream->getUUID();
        $recipe['craftingTag'] = $stream->getString();
        $recipe['priority'] = $stream->getVarInt();
        $recipe['networkId'] = $stream->getUnsignedVarInt();

        return $recipe;
    }

    private function encodeFurnaceRecipe(BinaryStream $stream, array $recipe): void {
        $stream->putVarInt($recipe['inputId']);

        if ($recipe['type'] === self::ENTRY_FURNACE_DATA) {
            $stream->putVarInt($recipe['inputMeta']);
        }

        $this->encodeItemStack($stream, $recipe['output']);
        $stream->putString($recipe['craftingTag']);
    }

    private function decodeFurnaceRecipe(BinaryStream $stream, array $recipe): array {
        $recipe['inputId'] = $stream->getVarInt();

        if ($recipe['type'] === self::ENTRY_FURNACE_DATA) {
            $recipe['inputMeta'] = $stream->getVarInt();
        }

        $recipe['output'] = $this->decodeItemStack($stream);
        $recipe['craftingTag'] = $stream->getString();

        return $recipe;
    }

    private function encodeMultiRecipe(BinaryStream $stream, array $recipe): void {
        $stream->putUUID($recipe['uuid']);
        $stream->putUnsignedVarInt($recipe['networkId'] ?? 0);
    }

    private function decodeMultiRecipe(BinaryStream $stream, array $recipe): array {
        $recipe['uuid'] = $stream->getUUID();
        $recipe['networkId'] = $stream->getUnsignedVarInt();
        return $recipe;
    }

    private function encodeShulkerBoxRecipe(BinaryStream $stream, array $recipe): void {
        $stream->putString($recipe['recipeId']);
        $stream->putString($recipe['craftingTag']);
    }

    private function decodeShulkerBoxRecipe(BinaryStream $stream, array $recipe): array {
        $recipe['recipeId'] = $stream->getString();
        $recipe['craftingTag'] = $stream->getString();
        return $recipe;
    }

    private function encodeSmithingTransformRecipe(BinaryStream $stream, array $recipe): void {
        $stream->putString($recipe['recipeId']);
        $this->encodeRecipeIngredient($stream, $recipe['template']);
        $this->encodeRecipeIngredient($stream, $recipe['base']);
        $this->encodeRecipeIngredient($stream, $recipe['addition']);
        $this->encodeItemStack($stream, $recipe['output']);
        $stream->putString($recipe['craftingTag']);
        $stream->putUnsignedVarInt($recipe['networkId'] ?? 0);
    }

    private function decodeSmithingTransformRecipe(BinaryStream $stream, array $recipe): array {
        $recipe['recipeId'] = $stream->getString();
        $recipe['template'] = $this->decodeRecipeIngredient($stream);
        $recipe['base'] = $this->decodeRecipeIngredient($stream);
        $recipe['addition'] = $this->decodeRecipeIngredient($stream);
        $recipe['output'] = $this->decodeItemStack($stream);
        $recipe['craftingTag'] = $stream->getString();
        $recipe['networkId'] = $stream->getUnsignedVarInt();
        return $recipe;
    }

    private function encodeRecipeIngredient(BinaryStream $stream, ?array $ingredient): void {
        if ($ingredient === null) {
            $stream->putVarInt(0);
            return;
        }

        $stream->putVarInt(count($ingredient['items'] ?? []));
        foreach ($ingredient['items'] ?? [] as $item) {
            $this->encodeItemStack($stream, $item);
        }
    }

    private function decodeRecipeIngredient(BinaryStream $stream): ?array {
        $count = $stream->getVarInt();

        if ($count === 0) {
            return null;
        }

        $ingredient = [];
        $ingredient['items'] = [];

        for ($i = 0; $i < $count; $i++) {
            $ingredient['items'][] = $this->decodeItemStack($stream);
        }

        return $ingredient;
    }

    private function encodeItemStack(BinaryStream $stream, object $item): void {
        $stream->putVarInt($item->id ?? 0);

        if (($item->id ?? 0) === 0) {
            return;
        }

        $stream->putVarInt($item->count ?? 1);
        $stream->putUnsignedVarInt($item->meta ?? 0);
    }

    private function decodeItemStack(BinaryStream $stream): object {
        $item = new \stdClass();
        $item->id = $stream->getVarInt();

        if ($item->id === 0) {
            return $item;
        }

        $item->count = $stream->getVarInt();
        $item->meta = $stream->getUnsignedVarInt();

        return $item;
    }

    private function encodePotionTypeRecipes(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->potionTypeRecipes));
        foreach ($this->potionTypeRecipes as $recipe) {
            $stream->putVarInt($recipe['inputId']);
            $stream->putVarInt($recipe['inputMeta']);
            $stream->putVarInt($recipe['ingredientId']);
            $stream->putVarInt($recipe['ingredientMeta']);
            $stream->putVarInt($recipe['outputId']);
            $stream->putVarInt($recipe['outputMeta']);
        }
    }

    private function decodePotionTypeRecipes(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        $this->potionTypeRecipes = [];

        for ($i = 0; $i < $count; $i++) {
            $this->potionTypeRecipes[] = [
                'inputId' => $stream->getVarInt(),
                'inputMeta' => $stream->getVarInt(),
                'ingredientId' => $stream->getVarInt(),
                'ingredientMeta' => $stream->getVarInt(),
                'outputId' => $stream->getVarInt(),
                'outputMeta' => $stream->getVarInt()
            ];
        }
    }

    private function encodePotionContainerRecipes(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->potionContainerRecipes));
        foreach ($this->potionContainerRecipes as $recipe) {
            $stream->putVarInt($recipe['inputId']);
            $stream->putVarInt($recipe['ingredientId']);
            $stream->putVarInt($recipe['outputId']);
        }
    }

    private function decodePotionContainerRecipes(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        $this->potionContainerRecipes = [];

        for ($i = 0; $i < $count; $i++) {
            $this->potionContainerRecipes[] = [
                'inputId' => $stream->getVarInt(),
                'ingredientId' => $stream->getVarInt(),
                'outputId' => $stream->getVarInt()
            ];
        }
    }

    private function encodeMaterialReducerRecipes(BinaryStream $stream): void {
        $stream->putUnsignedVarInt(count($this->materialReducerRecipes));
        foreach ($this->materialReducerRecipes as $recipe) {
            $stream->putVarInt($recipe['inputId']);
            $stream->putVarInt($recipe['inputMeta']);

            $stream->putUnsignedVarInt(count($recipe['outputs']));
            foreach ($recipe['outputs'] as $output) {
                $stream->putVarInt($output['id']);
                $stream->putVarInt($output['count']);
            }
        }
    }

    private function decodeMaterialReducerRecipes(BinaryStream $stream): void {
        $count = $stream->getUnsignedVarInt();
        $this->materialReducerRecipes = [];

        for ($i = 0; $i < $count; $i++) {
            $recipe = [];
            $recipe['inputId'] = $stream->getVarInt();
            $recipe['inputMeta'] = $stream->getVarInt();

            $outputCount = $stream->getUnsignedVarInt();
            $recipe['outputs'] = [];

            for ($j = 0; $j < $outputCount; $j++) {
                $recipe['outputs'][] = [
                    'id' => $stream->getVarInt(),
                    'count' => $stream->getVarInt()
                ];
            }

            $this->materialReducerRecipes[] = $recipe;
        }
    }

    public function addRecipe(array $recipe): void {
        $this->entries[] = $recipe;
    }

    public function getRecipes(): array {
        return $this->entries;
    }

    public function handle(object $handler): bool {
        return $handler->handleCraftingData($this);
    }
}