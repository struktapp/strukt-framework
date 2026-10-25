<?php

declare(strict_types=1);

namespace Strukt\Framework\Contract;

/**
 * Describes an installable framework bundle.
 *
 * A bundle contributes providers, middlewares, and console commands to an
 * application, and reports whether it has been published.
 */
interface Bundle
{
    /**
     * Returns the bundle name.
     *
     * @return string Bundle name.
     */
    public function getName(): string;

    /**
     * Returns the modules the bundle installs.
     *
     * @return list<string>|null Module names, or null when the bundle has none.
     */
    public function getModules(): ?array;

    /**
     * Returns the files the bundle installs.
     *
     * @return list<string>|null File paths, or null when the bundle has none.
     */
    public function getFiles(): ?array;

    /**
     * Reports whether the bundle has been published.
     *
     * @return bool Whether the bundle is published.
     */
    public function isPublished(): bool;

    /**
     * Returns the bundles the bundle requires.
     *
     * @return list<string>|null Required bundle names, or null when the bundle has none.
     */
    public function getRequirements(): ?array;

    /**
     * Runs before installation.
     */
    public function preInstall(): void;

    /**
     * Runs after installation.
     */
    public function postInstall(): void;

    /**
     * Returns the settings contributed for one application type.
     *
     * @param string $type Application type, such as `App:Idx` or `App:Cli`.
     * @return array<string, mixed> Settings for the application type.
     */
    public function getSettings(string $type): array;

    /**
     * Returns the display name used by the console.
     *
     * @return string Console name.
     */
    public function getCmdName(): string;
}
