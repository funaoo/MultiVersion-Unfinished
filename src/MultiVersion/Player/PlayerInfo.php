<?php
declare(strict_types=1);

namespace MultiVersion\Player;

class PlayerInfo {

    private Player $player;
    private string $deviceModel = "Unknown";
    private int $deviceOS = 0;
    private string $gameVersion = "Unknown";
    private int $uiProfile = 0;
    private string $languageCode = "en_US";
    private int $currentInputMode = 0;
    private int $defaultInputMode = 0;
    private string $deviceId = "";
    private int $guiScale = 0;
    private int $serverAddress = 0;
    private string $clientRandomId = "";
    private bool $isEditorMode = false;
    private bool $isThirdPartyNameVisible = false;
    private string $platformChatId = "";
    private array $extraData = [];
    private array $skinData = [];
    private float $lastPing = 0.0;
    private int $totalPacketsReceived = 0;
    private int $totalPacketsSent = 0;

    public function __construct(Player $player) {
        $this->player = $player;
        $this->loadFromPMPlayer();
    }

    private function loadFromPMPlayer(): void {
        $pmPlayer = $this->player->getPMPlayer();
        $playerInfo = $pmPlayer->getPlayerInfo();
        $extraData = $playerInfo->getExtraData();

        $this->deviceModel = $extraData['DeviceModel'] ?? 'Unknown';
        $this->deviceOS = $extraData['DeviceOS'] ?? 0;
        $this->gameVersion = $extraData['GameVersion'] ?? 'Unknown';
        $this->uiProfile = $extraData['UIProfile'] ?? 0;
        $this->languageCode = $extraData['LanguageCode'] ?? 'en_US';
        $this->currentInputMode = $extraData['CurrentInputMode'] ?? 0;
        $this->defaultInputMode = $extraData['DefaultInputMode'] ?? 0;
        $this->deviceId = $extraData['DeviceId'] ?? '';
        $this->guiScale = $extraData['GuiScale'] ?? 0;
        $this->clientRandomId = $extraData['ClientRandomId'] ?? '';
        $this->platformChatId = $extraData['PlatformChatId'] ?? '';
        $this->extraData = $extraData;

        $skin = $pmPlayer->getSkin();
        $this->skinData = [
            'skin_id' => $skin->getSkinId(),
            'skin_resource_patch' => $skin->getResourcePatch(),
            'geometry_name' => $skin->getGeometryName(),
            'is_persona' => $skin->isPersona(),
            'is_premium' => $skin->isPremium()
        ];
    }

    public function getDeviceModel(): string {
        return $this->deviceModel;
    }

    public function setDeviceModel(string $deviceModel): void {
        $this->deviceModel = $deviceModel;
    }

    public function getDeviceOS(): int {
        return $this->deviceOS;
    }

    public function getDeviceOSName(): string {
        return match($this->deviceOS) {
            1 => 'Android',
            2 => 'iOS',
            3 => 'macOS',
            4 => 'FireOS',
            5 => 'GearVR',
            6 => 'HoloLens',
            7 => 'Windows 10',
            8 => 'Windows',
            9 => 'Dedicated',
            10 => 'TVOS',
            11 => 'PlayStation',
            12 => 'Nintendo Switch',
            13 => 'Xbox',
            14 => 'Windows Phone',
            15 => 'Linux',
            default => 'Unknown'
        };
    }

    public function setDeviceOS(int $deviceOS): void {
        $this->deviceOS = $deviceOS;
    }

    public function getGameVersion(): string {
        return $this->gameVersion;
    }

    public function setGameVersion(string $gameVersion): void {
        $this->gameVersion = $gameVersion;
    }

    public function getUIProfile(): int {
        return $this->uiProfile;
    }

    public function getUIProfileName(): string {
        return match($this->uiProfile) {
            0 => 'Classic',
            1 => 'Pocket',
            default => 'Unknown'
        };
    }

    public function setUIProfile(int $uiProfile): void {
        $this->uiProfile = $uiProfile;
    }

    public function getLanguageCode(): string {
        return $this->languageCode;
    }

    public function setLanguageCode(string $languageCode): void {
        $this->languageCode = $languageCode;
    }

    public function getCurrentInputMode(): int {
        return $this->currentInputMode;
    }

    public function getCurrentInputModeName(): string {
        return match($this->currentInputMode) {
            0 => 'Unknown',
            1 => 'Mouse',
            2 => 'Touch',
            3 => 'GamePad',
            4 => 'MotionController',
            default => 'Unknown'
        };
    }

    public function setCurrentInputMode(int $currentInputMode): void {
        $this->currentInputMode = $currentInputMode;
    }

    public function getDefaultInputMode(): int {
        return $this->defaultInputMode;
    }

    public function setDefaultInputMode(int $defaultInputMode): void {
        $this->defaultInputMode = $defaultInputMode;
    }

    public function getDeviceId(): string {
        return $this->deviceId;
    }

    public function setDeviceId(string $deviceId): void {
        $this->deviceId = $deviceId;
    }

    public function getGuiScale(): int {
        return $this->guiScale;
    }

    public function setGuiScale(int $guiScale): void {
        $this->guiScale = $guiScale;
    }

    public function getClientRandomId(): string {
        return $this->clientRandomId;
    }

    public function setClientRandomId(string $clientRandomId): void {
        $this->clientRandomId = $clientRandomId;
    }

    public function isEditorMode(): bool {
        return $this->isEditorMode;
    }

    public function setEditorMode(bool $isEditorMode): void {
        $this->isEditorMode = $isEditorMode;
    }

    public function isThirdPartyNameVisible(): bool {
        return $this->isThirdPartyNameVisible;
    }

    public function setThirdPartyNameVisible(bool $visible): void {
        $this->isThirdPartyNameVisible = $visible;
    }

    public function getPlatformChatId(): string {
        return $this->platformChatId;
    }

    public function setPlatformChatId(string $platformChatId): void {
        $this->platformChatId = $platformChatId;
    }

    public function getExtraData(): array {
        return $this->extraData;
    }

    public function setExtraData(string $key, mixed $value): void {
        $this->extraData[$key] = $value;
    }

    public function getExtraDataValue(string $key): mixed {
        return $this->extraData[$key] ?? null;
    }

    public function hasExtraData(string $key): bool {
        return isset($this->extraData[$key]);
    }

    public function getSkinData(): array {
        return $this->skinData;
    }

    public function setSkinData(array $skinData): void {
        $this->skinData = $skinData;
    }

    public function getLastPing(): float {
        return $this->lastPing;
    }

    public function updatePing(): void {
        $networkSession = $this->player->getPMPlayer()->getNetworkSession();
        $this->lastPing = $networkSession->getPing();
    }

    public function getTotalPacketsReceived(): int {
        return $this->totalPacketsReceived;
    }

    public function incrementPacketsReceived(): void {
        $this->totalPacketsReceived++;
    }

    public function getTotalPacketsSent(): int {
        return $this->totalPacketsSent;
    }

    public function incrementPacketsSent(): void {
        $this->totalPacketsSent++;
    }

    public function isMobile(): bool {
        return in_array($this->deviceOS, [1, 2, 4, 10, 14], true);
    }

    public function isConsole(): bool {
        return in_array($this->deviceOS, [11, 12, 13], true);
    }

    public function isPC(): bool {
        return in_array($this->deviceOS, [3, 7, 8, 15], true);
    }

    public function isVR(): bool {
        return in_array($this->deviceOS, [5, 6], true);
    }

    public function isTouchscreen(): bool {
        return $this->currentInputMode === 2 || $this->defaultInputMode === 2;
    }

    public function isUsingController(): bool {
        return in_array($this->currentInputMode, [3, 4], true);
    }

    public function save(): array {
        return [
            'device_model' => $this->deviceModel,
            'device_os' => $this->deviceOS,
            'device_os_name' => $this->getDeviceOSName(),
            'game_version' => $this->gameVersion,
            'ui_profile' => $this->uiProfile,
            'language_code' => $this->languageCode,
            'current_input_mode' => $this->currentInputMode,
            'default_input_mode' => $this->defaultInputMode,
            'device_id' => $this->deviceId,
            'gui_scale' => $this->guiScale,
            'client_random_id' => $this->clientRandomId,
            'is_editor_mode' => $this->isEditorMode,
            'is_third_party_name_visible' => $this->isThirdPartyNameVisible,
            'platform_chat_id' => $this->platformChatId,
            'extra_data' => $this->extraData,
            'skin_data' => $this->skinData,
            'last_ping' => $this->lastPing,
            'total_packets_received' => $this->totalPacketsReceived,
            'total_packets_sent' => $this->totalPacketsSent
        ];
    }

    public function getClientInfo(): string {
        return sprintf(
            "%s (%s) - %s - %s",
            $this->deviceModel,
            $this->getDeviceOSName(),
            $this->gameVersion,
            $this->getCurrentInputModeName()
        );
    }

    public function toArray(): array {
        return $this->save();
    }
}