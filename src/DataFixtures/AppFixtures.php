<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use App\ValueObject\Money;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin'));
        $manager->persist($admin);

        $coffee = new Category('Coffee', 'coffee');
        $tea = new Category('Tea', 'tea');
        $accessories = new Category('Accessories', 'accessories');

        $manager->persist($coffee);
        $manager->persist($tea);
        $manager->persist($accessories);

        $products = [
            // name, slug, price (CZK), stock, category, description
            ['Ethiopia Yirgacheffe 250g', 'ethiopia-yirgacheffe-250g', 289.0, 40, $coffee, 'Bright, floral single-origin with notes of jasmine and citrus.'],
            ['Colombia Supremo 250g', 'colombia-supremo-250g', 249.0, 55, $coffee, 'Balanced and sweet with caramel and red apple.'],
            ['Brazil Santos 1kg', 'brazil-santos-1kg', 690.0, 25, $coffee, 'Low-acidity classic, great for espresso and milk drinks.'],
            ['House Espresso Blend 500g', 'house-espresso-blend-500g', 359.0, 60, $coffee, 'Our everyday espresso: chocolate, hazelnut, dried fig.'],
            ['Decaf Peru 250g', 'decaf-peru-250g', 279.0, 30, $coffee, 'Swiss Water decaf with cocoa and gentle sweetness.'],

            ['Sencha Green Tea 100g', 'sencha-green-tea-100g', 159.0, 45, $tea, 'Fresh, grassy Japanese green tea.'],
            ['Earl Grey 100g', 'earl-grey-100g', 139.0, 70, $tea, 'Black tea scented with natural bergamot oil.'],
            ['Rooibos Vanilla 100g', 'rooibos-vanilla-100g', 129.0, 50, $tea, 'Caffeine-free rooibos with smooth vanilla.'],
            ['Darjeeling First Flush 100g', 'darjeeling-first-flush-100g', 199.0, 20, $tea, 'Delicate, muscatel high-grown Indian black tea.'],

            ['French Press 1L', 'french-press-1l', 549.0, 15, $accessories, 'Borosilicate glass press for a full-bodied brew.'],
            ['Ceramic Mug 300ml', 'ceramic-mug-300ml', 189.0, 80, $accessories, 'Simple, sturdy stoneware mug.'],
            ['Hand Grinder', 'hand-grinder', 890.0, 12, $accessories, 'Ceramic burr grinder with adjustable grind size.'],
        ];

        foreach ($products as [$name, $slug, $price, $stock, $category, $description]) {
            $product = new Product($name, $slug, Money::fromMajorUnits($price), $category);
            $product->setStock($stock);
            $product->setDescription($description);

            $manager->persist($product);
        }

        $manager->flush();
    }
}
