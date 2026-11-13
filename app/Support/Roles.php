<?php

namespace App\Support;

/**
 * Definisi role & permission tunggal (sumber kebenaran untuk seeder, policy, dan test).
 */
final class Roles
{
    public const SUPER_ADMIN = 'super_admin';

    public const ADMINISTRATOR = 'administrator';

    public const MARKETING = 'marketing';

    public const SALES = 'sales';

    public const PANEL_ROLES = [self::SUPER_ADMIN, self::ADMINISTRATOR, self::MARKETING, self::SALES];

    /** Resource CRUD standar → permission {resource}.{view,create,update,delete}. */
    public const CRUD_RESOURCES = [
        'pages', 'products', 'product_categories', 'campaigns', 'banners', 'promotions', 'testimonials',
        'case_studies', 'industry_solutions', 'partners', 'statistics', 'faqs', 'media', 'navigation',
    ];

    public const EXTRA_PERMISSIONS = [
        'pages.publish', 'pages.restore', 'builder.edit', 'builder.custom_html',
        'leads.view_all', 'leads.view_assigned', 'leads.create', 'leads.update', 'leads.assign', 'leads.add_note',
        'leads.export', 'leads.delete', 'leads.reports',
        'settings.manage', 'settings.custom_scripts',
        'users.manage', 'roles.manage', 'activity.view', 'cache.manage',
    ];

    /** @return list<string> */
    public static function allPermissions(): array
    {
        $crud = [];
        foreach (self::CRUD_RESOURCES as $resource) {
            foreach (['view', 'create', 'update', 'delete'] as $ability) {
                $crud[] = "{$resource}.{$ability}";
            }
        }

        return array_values(array_unique([...$crud, ...self::EXTRA_PERMISSIONS]));
    }

    /** @return array<string, list<string>> role => permissions (super_admin lewat Gate::before) */
    public static function matrix(): array
    {
        $crud = fn (array $resources, array $abilities = ['view', 'create', 'update', 'delete']) => collect($resources)
            ->flatMap(fn ($r) => collect($abilities)->map(fn ($a) => "{$r}.{$a}"))->all();

        $marketingResources = ['campaigns', 'banners', 'promotions', 'testimonials', 'case_studies',
            'industry_solutions', 'partners', 'statistics', 'faqs'];

        return [
            self::SUPER_ADMIN => self::allPermissions(),
            self::ADMINISTRATOR => array_values(array_unique([
                ...$crud(self::CRUD_RESOURCES),
                'pages.publish', 'pages.restore', 'builder.edit',
                'leads.view_all', 'leads.view_assigned', 'leads.create', 'leads.update', 'leads.assign',
                'leads.add_note', 'leads.export', 'leads.reports',
                'activity.view', 'cache.manage',
            ])),
            self::MARKETING => array_values(array_unique([
                ...$crud($marketingResources),
                ...$crud(['media'], ['view', 'create', 'update']),
                ...$crud(['pages', 'products'], ['view']),
                'leads.view_assigned', 'leads.update', 'leads.add_note',
            ])),
            self::SALES => ['leads.view_assigned', 'leads.update', 'leads.add_note'],
        ];
    }

    public static function label(string $role): string
    {
        return match ($role) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMINISTRATOR => 'Administrator',
            self::MARKETING => 'Marketing',
            self::SALES => 'Sales',
            default => ucfirst($role),
        };
    }
}
