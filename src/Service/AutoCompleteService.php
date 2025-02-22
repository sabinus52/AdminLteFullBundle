<?php

declare(strict_types=1);

/**
 * This file is part of OlixBackOfficeBundle.
 * (c) Sabinus52 <sabinus52@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Olix\BackOfficeBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Classe de service de l'autocomplétion des objets Select2
 * Permet de retourner les items en AJAX.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
class AutoCompleteService
{
    /**
     * Constructeur.
     */
    public function __construct(protected EntityManagerInterface $entityManager, protected FormFactoryInterface $formFactory)
    {
    }

    /**
     * Retourne les résultats trouvés depuis un recherche "Select2".
     *
     * @return array<mixed>
     */
    public function getResults(string $formType, Request $request): array
    {
        $accessor = new PropertyAccessor();

        // Paramètres du Request
        $term = $request->get('term');
        $page = (int) $request->get('page', 0); /** @phpstan-ignore cast.int */
        $widget = (string) $request->get('widget'); /** @phpstan-ignore cast.string */

        // Info du formulaire en cours utilisé
        $form = $this->formFactory->create($formType);
        /** @var string[] $select2Options */
        $select2Options = $form->get($widget)->getConfig()->getOptions();
        $count = (int) $select2Options['page_limit'];

        // Recherche des items
        $query = $this->entityManager->createQueryBuilder()
            ->select('entity')
            ->from($select2Options['class'], 'entity')
            ->andWhere('entity.'.$select2Options['class_property'].' LIKE :term')
            ->setParameter('term', '%'.$term.'%')
            ->orderBy('entity.'.$select2Options['class_property'], 'ASC')
        ;

        if (is_callable($select2Options['callback'])) {
            $callFunction = $select2Options['callback'];
            $callFunction($query);
        }

        // Si tous les items ou bien par page
        if (0 === $page) {
            $query = $query->getQuery();
            $items = $query->getResult();
        } else {
            $query = $query->setFirstResult(($page - 1) * $count)
                ->setMaxResults($count)
                ->getQuery()
            ;
            $items = new Paginator($query, true);
        }

        // Mapping des résultats
        $results = [];
        /** @var object[] $items */
        foreach ($items as $item) {
            $results[] = [
                'id' => $accessor->getValue($item, $select2Options['class_pkey']),
                'text' => $accessor->getValue($item, $select2Options['class_label']),
            ];
        }

        // Retourne les résultats paginés
        if (0 !== $page) {
            return [
                'results' => $results,
                'more' => (($page * $count) < count($items)),
            ];
        }

        // Retourne tous les résultats
        return $results;
    }
}
