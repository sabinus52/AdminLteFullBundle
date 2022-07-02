<?php

declare(strict_types=1);

/**
 *  This file is part of OlixBackOfficeBundle.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Olix\BackOfficeBundle\Datatable\Column;

use Closure;
use Exception;
use Olix\BackOfficeBundle\Datatable\Action\Action;
use Olix\BackOfficeBundle\Datatable\Helper;
use Olix\BackOfficeBundle\Datatable\HtmlContainerTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @see https://github.com/stwe/DatatablesBundle
 * @SuppressWarnings(PHPMD)
 */
class ActionColumn extends AbstractColumn
{
    /*
     * This Column has a 'start_html' and a 'end_html' option.
     * <startHtml> action1 action2 actionX </endHtml>
     */
    use HtmlContainerTrait;

    /**
     * The Actions container.
     * A required option.
     *
     * @var array<mixed>
     */
    protected $actions;

    // -------------------------------------------------
    // ColumnInterface
    // -------------------------------------------------

    /**
     * {@inheritdoc}
     *
     * @param string|null $dql
     *
     * @return bool
     */
    public function dqlConstraint($dql)
    {
        return null === $dql ? true : false;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isSelectColumn()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<mixed> $row
     *
     * @return self
     */
    public function addDataToOutputArray(array &$row)
    {
        $actionRowItems = [];

        /** @var Action $action */
        foreach ($this->actions as $actionKey => $action) {
            $actionRowItems[$actionKey] = $action->callRenderIfClosure($row);
        }

        // @phpstan-ignore-next-line
        $row['sg_datatables_actions'][$this->getIndex()] = $actionRowItems;
    }

    /**
     * {@inheritdoc}
     *
     * @param array<mixed> $row
     *
     * @return self
     */
    public function renderSingleField(array &$row)
    {
        $parameters = [];
        $attributes = [];
        $values = [];

        /** @var Action $action */
        foreach ($this->actions as $actionKey => $action) {
            $routeParameters = $action->getRouteParameters();
            if (\is_array($routeParameters)) {
                foreach ($routeParameters as $key => $value) {
                    if (isset($row[$value])) {
                        $parameters[$actionKey][$key] = $row[$value];
                    } else {
                        $path = Helper::getDataPropertyPath($value);
                        $entry = $this->accessor->getValue($row, $path);

                        if (!empty($entry)) {
                            $parameters[$actionKey][$key] = $entry;
                        } else {
                            $parameters[$actionKey][$key] = $value;
                        }
                    }
                }
            } elseif ($routeParameters instanceof Closure) {
                $parameters[$actionKey] = \call_user_func($routeParameters, $row);
            } else {
                $parameters[$actionKey] = [];
            }

            $actionAttributes = $action->getAttributes();
            if (\is_array($actionAttributes)) {
                $attributes[$actionKey] = $actionAttributes;
            } elseif ($actionAttributes instanceof Closure) {
                $attributes[$actionKey] = \call_user_func($actionAttributes, $row);
            } else {
                $attributes[$actionKey] = [];
            }

            if ($action->isButton()) {
                if (null !== $action->getButtonValue()) {
                    if (isset($row[$action->getButtonValue()])) {
                        $values[$actionKey] = $row[$action->getButtonValue()];
                    } else {
                        $values[$actionKey] = $action->getButtonValue();
                    }

                    if (\is_bool($values[$actionKey])) {
                        $values[$actionKey] = (int) $values[$actionKey];
                    }

                    if (true === $action->isButtonValuePrefix()) {
                        $values[$actionKey] = 'sg-datatables-'.$this->getDatatableName().'-action-button-'.$actionKey.'-'.$values[$actionKey];
                    }
                } else {
                    $values[$actionKey] = null;
                }
            }
        }

        // @phpstan-ignore-next-line
        $row[$this->getIndex()] = $this->twig->render(
            $this->getCellContentTemplate(),
            [
                'actions' => $this->actions,
                'route_parameters' => $parameters,
                'attributes' => $attributes,
                'values' => $values,
                'render_if_actions' => $row['sg_datatables_actions'][$this->index],
                'start_html_container' => $this->startHtml,
                'end_html_container' => $this->endHtml,
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param array<mixed> $row
     *
     * @return self
     */
    public function renderToMany(array &$row)
    {
        throw new Exception('ActionColumn::renderToMany(): This function should never be called.');
    }

    /**
     * {@inheritdoc}
     */
    public function getCellContentTemplate()
    {
        return '@OlixBackOffice/Datatable/render/action.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnType()
    {
        return parent::ACTION_COLUMN;
    }

    // -------------------------------------------------
    // Options
    // -------------------------------------------------

    /**
     * @return $this
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->remove('dql');
        $resolver->remove('data');
        $resolver->remove('default_content');

        // the 'orderable' option is removed, but via getter it returns 'false' for the view
        $resolver->remove('orderable');
        $resolver->remove('order_data');
        $resolver->remove('order_sequence');

        // the 'searchable' option is removed, but via getter it returns 'false' for the view
        $resolver->remove('searchable');

        $resolver->remove('join_type');
        $resolver->remove('type_of_field');

        $resolver->setRequired(['actions']);

        $resolver->setDefaults([
            'start_html' => null,
            'end_html' => null,
            'class_name' => 'text-right text-nowrap',
        ]);

        $resolver->setAllowedTypes('actions', 'array');
        $resolver->setAllowedTypes('start_html', ['null', 'string']);
        $resolver->setAllowedTypes('end_html', ['null', 'string']);

        return $this;
    }

    // -------------------------------------------------
    // Getters && Setters
    // -------------------------------------------------

    /**
     * @return Action[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param array<mixed> $actions
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setActions(array $actions)
    {
        if (\count($actions) > 0) {
            foreach ($actions as $action) {
                $this->addAction($action);
            }
        } else {
            throw new Exception('ActionColumn::setActions(): The actions array should contain at least one element.');
        }

        return $this;
    }

    /**
     * Add action.
     *
     * @param array<mixed> $action
     *
     * @return $this
     */
    public function addAction(array $action)
    {
        $newAction = new Action($this->datatableName);
        $this->actions[] = $newAction->set($action);

        return $this;
    }

    /**
     * Remove action.
     *
     * @return $this
     */
    public function removeAction(Action $action)
    {
        foreach ($this->actions as $k => $a) {
            if ($action === $a) {
                unset($this->actions[$k]);

                break;
            }
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getOrderable()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function getSearchable()
    {
        return false;
    }
}
