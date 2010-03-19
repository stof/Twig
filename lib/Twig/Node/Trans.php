<?php

/*
 * This file is part of Twig.
 *
 * (c) 2010 Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Represents a trans node.
 *
 * @package    twig
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id$
 */
class Twig_Node_Trans extends Twig_Node
{
  protected $count, $body, $plural;

  public function __construct($count, Twig_NodeList $body, $plural, $lineno, $tag = null)
  {
    parent::__construct($lineno, $tag);

    $this->count = $count;
    $this->body = $body;
    $this->plural = $plural;
  }

  public function __toString()
  {
    return get_class($this).'('.$this->body.', '.$this->count.')';
  }

  public function compile($compiler)
  {
    $compiler->addDebugInfo($this);

    list($msg, $vars) = $this->compileString($this->body);

    if (false !== $this->plural)
    {
      list($msg1, $vars1) = $this->compileString($this->plural);
    }

    $function = false === $this->plural ? 'gettext' : 'ngettext';

    if ($vars || false !== $this->plural)
    {
      $compiler
        ->write('echo strtr('.$function.'(')
        ->string($msg)
      ;

      if (false !== $this->plural)
      {
        $compiler
          ->raw(', ')
          ->string($msg1)
          ->raw(', abs(')
          ->subcompile($this->count)
          ->raw(')')
        ;
      }

      $compiler->raw('), array(');

      foreach ($vars as $var)
      {
        $compiler
          ->string('%'.$var->getName().'%')
          ->raw(' => ')
          ->subcompile($var)
          ->raw(', ')
        ;
      }

      if (false !== $this->plural)
      {
        $compiler
          ->string('%count%')
          ->raw(' => abs(')
          ->subcompile($this->count)
          ->raw('), ')
        ;
      }

      $compiler->raw("));\n");
    }
    else
    {
      $compiler
        ->write('echo '.$function.'(')
        ->string($msg)
        ->raw(");\n")
      ;
    }
  }

  public function getBody()
  {
    return $this->body;
  }

  public function getPlural()
  {
    return $this->plural;
  }

  public function getCount()
  {
    return $this->count;
  }

  protected function compileString(Twig_NodeList $body)
  {
    $msg = '';
    $vars = array();
    foreach ($body->getNodes() as $i => $node)
    {
      if ($node instanceof Twig_Node_Text)
      {
        $msg .= $node->getData();
      }
      elseif ($node instanceof Twig_Node_Print && $node->getExpression() instanceof Twig_Node_Expression_Name)
      {
        $msg .= sprintf('%%%s%%', $node->getExpression()->getName());
        $vars[] = $node->getExpression();
      }
      else
      {
        throw new Twig_SyntaxError(sprintf('The text to be translated with "trans" can only contain references to simple variable'), $this->lineno);
      }
    }

    return array(trim($msg), $vars);
  }
}
