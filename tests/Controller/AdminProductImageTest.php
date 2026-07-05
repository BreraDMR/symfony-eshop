<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdminProductImageTest extends WebTestCase
{
    public function testAdminCanUploadProductImage(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $category = new Category('Coffee', 'coffee-'.uniqid());
        $em->persist($category);

        $admin = new User('image-admin-'.uniqid().'@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword('not-used-by-loginUser');
        $em->persist($admin);
        $em->flush();

        $client->loginUser($admin);

        // Keep the test idempotent: drop a product left over from a previous run.
        $existing = static::getContainer()->get(ProductRepository::class)
            ->findOneBy(['slug' => 'test-uploaded-coffee']);
        if ($existing !== null) {
            $em->remove($existing);
            $em->flush();
        }

        $crawler = $client->request('GET', '/admin/products/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Create')->form();
        $form['product[name]'] = 'Test Uploaded Coffee';
        $form['product[price]'] = '349';
        $form['product[stock]'] = '7';
        $form['product[description]'] = 'Created by the upload test.';
        $form['product[category]']->select((string) $category->getId());
        $form['product[imageFile]']->upload($this->fixtureImage());

        $client->submit($form);
        self::assertResponseRedirects('/admin/products');

        $product = static::getContainer()->get(ProductRepository::class)
            ->findOneBy(['slug' => 'test-uploaded-coffee']);

        self::assertNotNull($product);
        self::assertNotNull($product->getImageFilename(), 'The uploaded image filename should be stored.');

        $stored = static::getContainer()->getParameter('kernel.project_dir')
            .'/var/test-uploads/'.$product->getImageFilename();
        self::assertFileExists($stored);

        @unlink($stored);
        $cleanupEm = static::getContainer()->get(EntityManagerInterface::class);
        $cleanupEm->remove($cleanupEm->getReference($product::class, $product->getId()));
        $cleanupEm->flush();
    }

    private function fixtureImage(): string
    {
        $source = \dirname(__DIR__, 2).'/public/images/products/hand-grinder.jpg';
        $tmp = tempnam(sys_get_temp_dir(), 'upl').'.jpg';
        copy($source, $tmp);

        return $tmp;
    }
}
