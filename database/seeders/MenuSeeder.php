<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    public function run()
    {
        $menus = [
            [
                'name' => 'Home',
                'url' => '/',
                'order' => 1
            ],
            [
                'name' => 'Properties',
                'url' => '/properties',
                'order' => 2,
                'children' => [
                    ['name' => 'For Sale', 'url' => '/properties/for-sale', 'order' => 1],
                    ['name' => 'For Rent', 'url' => '/properties/for-rent', 'order' => 2],
                ]
            ],
            [
                'name' => 'Services',
                'url' => '/services',
                'order' => 3,
                'children' => [
                    ['name' => 'Buying', 'url' => '/services/buying', 'order' => 1],
                    ['name' => 'Selling', 'url' => '/services/selling', 'order' => 2],
                    ['name' => 'Renting', 'url' => '/services/renting', 'order' => 3],
                ]
            ],
            [
                'name' => 'About',
                'url' => '/about',
                'order' => 4
            ],
            [
                'name' => 'Contact',
                'url' => '/contact',
                'order' => 5
            ],
            [
                'name' => 'Calculators',
                'url' => '/calculators',
                'order' => 6
            ],
        ];

        foreach ($menus as $menuData) {
            $this->createMenu($menuData);
        }
    }

    private function createMenu($menuData, $parentId = null)
    {
        $children = $menuData['children'] ?? [];
        unset($menuData['children']);

        $menuData['parent_id'] = $parentId;
        $menu = Menu::create($menuData);

        foreach ($children as $childData) {
            $this->createMenu($childData, $menu->id);
        }
    }
}