# **@Pdic** - PHP Dependency Injection Class

## Sommaire

1) [Introduction](#introduction)
2) [Installation](#installation)
3) [Injections](#injections):
    - [Injections sélectives](#iselective)
    - [Autowiring](#iclasses)
    - [Paramètres personnels](#imethodes)
4) [Invocation de méthodes)](#invoke)
5) [Licence et Utilisations](#licence)
6) [A propos](#apropos)

<br>

<a name="introduction"></a>

## Introduction

Tout d'abord, l'injection de dépendance est un [Design Pattern](https://refactoring.guru/fr/design-patterns). Par ailleurs, en PHP, une normalisation est proposée pour ce Pattern: [PSR-11](https://www.php-fig.org/psr/psr-11/).
L'injection de dépendances s'inscrit dans les principes de la programmation objet ([POO](https://grafikart.fr/formations/programmation-objet-php)).

L'injection de dépendances est un mécanisme visant à simplifier la transmission de services, d'instances d'objets communs au sein d'une application.

Il est souvent pratiqué de transmettre de classes en classes, l'instance d'un objet, par exemple, regroupant des fonctionnalités d'utilisations communes. Plutôt fastidieux et difficilement maintenanble comme le montre cet exemple en PHP:

```php
<?php

class User
{

    private string $name;

    public function __construct(string $name = 'bob') {
        $this->name = $name;
    }

    public function setName(string $name) {
        $this->name = $name;
    }

    public function getName(): string {
        return $this->name;
    }

}

class FirstTreatment
{

    private User $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function execute(string $prefix): string {
        return $prefix . strtoupper($this->user->getName());
    }

}

class SecondTreatment
{

    private User $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function execute(string $prefix): string {
        return $prefix . strtolower($this->user->getName());
    }

}

class UserFormatter
{

    private User $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function toUpper(string $prefix = '@'): string
    {
        $treatment = new FirstTreatment($this->user);
        return $treatment->execute($prefix);
    }

    public function toLower(string $prefix = '@'): string
    {
        $treatment = new SecondTreatment($this->user);
        return $treatment->execute($prefix);
    }

}
```

Que nous utiliserions comme suit:

```php
<?php

$user = new User('Christophe');

$formatter = new UserFormatter($user);

var_dump($formatter->toLower());
var_dump($formatter->toUpper());

// string(11) "@christophe"
// string(11) "@CHRISTOPHE"
```

Dans l'exemple ci-dessus, tout le monde dépend de tout le monde et l'instance de l'objet *User* se transmet de classes en classes.

Une solution existe: **L'injection de dépendances**

Pour celà, je vous propose une toute petite librairie PHP, très simple d'utilisation, et parfaitement efficace, pouvant répondre à 3 besoins particuliers en reposant sur 2 principes:

- L'injection par ***Container*** de dépendances identifiées
- Liaisons automatiques des dépendances, aussi appelée ***Autowiring***

<br>

<a name="installation"></a>

## Installation

Encore une fois, simplicité est maitre mot.

Copiez/Collez le dossier *'pdic'* et son contenu dans le dossier de votre choix (par exemple: *'libs'*) au niveau de votre applications. Puis, dans le script principal de celle-ci, intégrez **@PDic** comme suit:

```php
<?php
....

namespace MonApplie;

require_once './libs/pdic/pdic.inc.php';

use Libs\Pdic\Pdic;

....
```

Et c'est tout!

Passons à l'utilisation.

<br>

<a name="injections"></a>

## Utilisations

**@Pdic** repose sur l'utilisation d'un conteneur de dépendances accessible partout au sein de l'application qui l'utilise. Par ailleurs, ce conteneur peut être alimenté tout au long de l'éxécution de celle-ci pour s'adapter aux situations facilement. Toutefois, et malgré une relative souplesse, des principes sont à respecter.

Nous allons en aborder les 3 principaux.

<a name="iselective"></a>

### Injections sélectives

L'*injection sélective* est le premier principe proposé par **@Pdic**. Ce principe rend accessible de partout toutes classes connues et instanciables dans votre application. Ceci permet donc de charger une classe et de réutiliser une seule et unique même instance à chaque fois que vous en auriez besoin.

Dans l'exemple proposé en [introduction](#introduction), nous avions créé deux classes *FirstTreatment* et *SecondTreatment*. Ce sont des outils de traitement, travaillant toutes deux sur le nom d'un utilisateur. Tantôt, pour le mettre en minuscule, tantôt pour le mettre en majuscule.

Etant de simple outils fonctionnels, nous pourrions en avoir besoin à plusieurs reprises dans le code de l'application. Par exemple, nous pourrions avoir besoin de traiter plusieurs utilisateurs. Nous aurons aussi surement besoin de ranger proprement ces outils dans un dossier précis.

Certains diront, un Design Pattern connu pourrait répondre à ce besoin: [**Le Singleton**](https://grafikart.fr/tutoriels/singleton-569). Certes. Mais celà ne resoudrait pas notre problème de dépendances courreuses de classes.

**@Pdic** propose donc une solution pouvant regrouper ces 2 concepts.

Reprenons l'exemple ci-dessus:

```php
class UserFormatter
{

    private User $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function toUpper(): string {
        $treatment = new FirstTreatment($this->user);
        return $treatment->execute('@');
    }

    public function toLower(): string {
        $treatment = new SecondTreatment($this->user);
        return $treatment->execute('@');
    }

}
```

La classe *UserFormatter* instancie à chaque fois les 2 outils de traitement pour assouvir ses besoins. A chaque fois, nous griognotons un peu plus sur la mémoire et donc les performances de notre application.

Ces outils, non seulement, fixes, rangés, et surement regroupés au sein d'un espace de nom leur étant propre, nécessite qu'on leur passe en paramètre du constructeur une instance d'objet *User*!

Nous pourrions peut être simplifier par:

```php
// Définit un nouvel utilisateur puis stocke cette instance
// pour être injectée partout où un type User sera demandé.
Pdic::set(new User('Christophe'));

// Retourne l'instance de la class UserFormatter.
// Comme on le constate, l'utilisateur n'a plus besoin d'être définit
// à ce stade! Il vient d'être automatiquement injecté dans le constructeur
// de l'objet UserFormatter lors de sa première instanciation!
$formatter = Pdic::get(UserFormatter::class);

var_dump($formatter->toLower());
var_dump($formatter->toUpper());

// le résultat est le même, victoire de canard!
// string(11) "@christophe"
// string(11) "@CHRISTOPHE"
```

Simplicité et performances réunies! Mais un gros bémol réside dans cette pratique...
Impossible de formatter plusieurs utilisateurs différents!

```php
Pdic::set(new User('Christophe'));

$formatter1 = Pdic::get(UserFormatter::class);
var_dump('-> formatter1');
var_dump($formatter1->toLower());
var_dump($formatter1->toUpper());

$formatter2 = Pdic::get(UserFormatter::class);
var_dump('-> formatter2');
var_dump($formatter2->toLower());
var_dump($formatter2->toUpper());

var_dump('-> formatter1 === formatter2');
var_dump($formatter1 === $formatter2);

// string(13) "-> formatter1"
// string(11) "@christophe"
// string(11) "@CHRISTOPHE"
// string(13) "-> formatter2"
// string(11) "@christophe"
// string(11) "@CHRISTOPHE"
// string(28) "-> formatter1 === formatter2"
// bool(true)
```

Nous constatons, non seulement, que les 2 *$formatter* sont exactement la même instance de la classe *UserFormatter* (le principe du Singleton) mais qu'en plus, ils se partagent le même utilisateur!

Ce phénomène pouvant devenir une lacune dans certains cas c'est pourquoi **@Pdic** propose les 2 solutions suivantes.

<a name="iclasses"></a>

### Dépendences dans les méthodes de classes - Autowiring

Prenons un nouvel exemple:

```php
class Renderer
{
    protected string $identity = "i'm the common renderer";

    public function render(string $message)
    {
        var_dump($this->identity);
        var_dump($message);
    }
}

class HtmlRenderer extends Renderer
{
    protected string $identity = "i'm the HTML renderer";
}

class JsonRenderer extends Renderer
{
    protected string $identity = "i'm the JSON renderer";
}

class Response
{
    private Renderer $renderer;

    public function __construct(Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function send(string $message = 'mon message')
    {
        $this->renderer->render($message);
    }
}

$response = Pdic::get(Response::class);
$response->send("bob est grand");

// string(23) "i'm the common renderer"
// string(13) "bob est grand"
```

Cet exemple simule une réponse que pourrait envoyer un *Controller* à la fin de son traitement.

Pour ce faire, nous déclarons utiliser 2 principaux objets, un *Renderer* chargé de convertir la réponse au format souhaité, et l'objet *Response* lui même contenant les actions disponibles pour le *Controller*.

```php
/*
class Response
{
    private Renderer $renderer;

    public function __construct(Renderer $renderer)
    {
....
*/

$response = Pdic::get(Response::class);
```

Nous instancions une nouvelle réponse. Mais comme vous le constatez, nous ne passons pas en paramètre du constructeur, l'instance d'un objet *Renderer* attendu. **@Pdic** s'occupe de tout!

Toutefois, **@Pdic** ne sait qu'instancier un objet dont il reconnait le type définit pour un argument du constructeur. Dans notre cas, le paramètre *$renderer* est un objet *Renderer*.

Alors comment faire pour définir un moteur de rendu spécifique? Comment dire à **@Pdic** d'instancier tel ou tel moteur de rendu lorsqu'il reconnait un argument de type *Renderer* ?

Encore une fois, simplicité de mise, il suffit juste de lui dire ;-) Et pour celà, nous avons a notre disposition, **les définitions**.


```
Pdic::setDefinition($parentClass, $methodName, [
    $paramClass => $classInstance,
]);
```

```php
Pdic::setDefinition(Response::class, '__construct', [
    Renderer::class => new HtmlRenderer(),
]);

$response = Pdic::get(Response::class);
$response->send("bob est grand");

// string(21) "i'm the HTML renderer"
// string(13) "bob est grand"
```

Magique !

Notre réponse est désormais prise en charge par le moteur de rendu HTML ! Désormais **@Pdic** convertira automatiquement le paramêtre définissant le moteur de rendu en une instance du moteur de rendu HTML.

Bien évidement, nous pouvons dire à **@Pdic** d'injecter à tous les objets possédant la méthode *__construct* et un paramètre du type *Renderer*:

```php
Pdic::setDefinition('*', '__construct', [
    Renderer::class => new HtmlRenderer(),
]);
```

Nous pouvons aussi lui dire d'injecter le paramètre à toute les méthodes d'une classe possédant un argument de ce type!

```php
Pdic::setDefinition(Response::class, '*', [
    Renderer::class => new HtmlRenderer(),
]);
```

Et même, ordonner à **@Pdic** d'injecter à toute les classes, et méthodes:

```php
Pdic::setDefinition('*', '*', [
    Renderer::class => new HtmlRenderer(),
]);
```

Cas pratique:

```php
// $_ENV['MyAppType'] = 'api';
$_ENV['MyAppType'] = 'api';

$envType = isset($_ENV['MyAppType'])
    ? strtolower(trim($_ENV['MyAppType']))
    : '';

$renderer = new HtmlRenderer();
if ($envType == 'api') {
    $renderer = new JsonRenderer();
}

Pdic::setDefinition(Response::class, '*', [
    Renderer::class => $renderer,
]);

$response = Pdic::get(Response::class);
$response->send("message to render");
```

<a name="imethodes"></a>

### Dépendances dans les méthodes de classes - Paramètres personnels

Modifions la classe *Response* comme suit:

```php
class Response
{
    private Renderer $renderer;

    public function __construct(Renderer $renderer, string $commonParam)
    {
        $this->renderer = $renderer;
        var_dump($commonParam);
    }

    public function send(string $message = 'mon message')
    {
        $this->renderer->render($message);
    }
}

$response = Pdic::get(Response::class);
$response->send("message to render");
```

Le constructeur prend un nouvel argument *$commonParam*.

Si nous executons le code tel quel, PHP vient à nous répréhender:

```
Fatal error: Uncaught Exception: Unknown param `commonParam` in /home/christophe/OneDrive/Documents/Projets/Php/Framel/libs/pdic/pdic.inc.php:177 Stack trace: #0 /home/christophe/OneDrive/Documents/Projets/Php/Framel/libs/pdic/pdic.inc.php(251): Framel\Libs\Pdic\Pdic::resolve() #1 /home/christophe/OneDrive/Documents/Projets/Php/Framel/libs/pdic/pdic.inc.php(287): Framel\Libs\Pdic\Pdic::instanciate() #2 /home/christophe/OneDrive/Documents/Projets/Php/Framel/framel.php(64): Framel\Libs\Pdic\Pdic::get() #3 {main} thrown in /home/christophe/OneDrive/Documents/Projets/Php/Framel/libs/pdic/pdic.inc.php on line 177
```

Et il aura raison! A aucun moment nous definissons ce paramètre dans le constructeur!

Une solution existe. Pour celà, il faut définir les paramètres personnels dans un tableau que l'on ajoutera aux méthodes *Pdic::get()* et *Pdic::invoke()*:

```php
....

$response = Pdic::get(Response::class, [
    Response::class => [
        '__construct' => [
            'commonParam' => "yeah! i'm the commonParam and i'm defined!",
        ],
    ]
]);
$response->send("message to render");
```

Nous pouvons traduire les lignes ci-dessus par:
- Je créé une instance de la classe *Response*, et je definis pour la méthode *__construct* de la classe *Response*, le paramètre *commonParam* avec la valeur *"yeah! i'm the commonParam and i'm defined!"*.

<br>

<a name="invoke"></a>

## Invocation de méthodes

Depuis le début nous voyons comment utiliser les classes et leurs instanciations. Mais parfois, une classe ne possède pas de constructeurs! **@Pdic** y a pensé et propose sa solution: *Pdic::invokde()*

```
public static function invoke(string $class, string $methodName, array $methodParams = [])
```

Maintenant que vous connaissez un peu mieux **@Pdic**, completons notre précédent exemple:

```php
class Renderer
{
    protected string $identity = "i'm the common renderer";

    public function render(string $message)
    {
        var_dump('renderer: ' . $this->identity);
        var_dump('rendered message: ' . $message);
        var_dump('------------------------');
    }
}

class HtmlRenderer extends Renderer
{
    protected string $identity = "i'm the HTML renderer";
}

class JsonRenderer extends Renderer
{
    protected string $identity = "i'm the JSON renderer";
}

class Response
{
    private Renderer $renderer;

    public function __construct(Renderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function set(string $message)
    {
        $this->renderer->render($message);
    }
}

class Engine
{
    public function __construct($name = 'engine')
    {
        $this->name = $name;
    }
}

class MyController1
{
    public function home(Engine $engine, Response $response, string $title)
    {
        var_dump('controller title: ' . $title);
        var_dump('engine: ' . $engine->name);

        $response->set("ma page home");
    }

    public function contact(Engine $engine, Response $response, string $title)
    {
        var_dump('title: ' . $title);
        var_dump('engine: ' . $engine->name);

        $response->set("ma page contact");
    }
}

// préparation des injections

Pdic::setDefinition(Response::class, '*', [Renderer::class => new HtmlRenderer()]);
Pdic::setDefinition(MyController1::class, 'home', [Engine::class => new Engine("i'm the home engine")]);
Pdic::setDefinition(MyController1::class, 'contact', [Engine::class => new Engine("i'm the contact engine")]);

$myController1Params = [
    MyController1::class =>
    [
        'home' => ['title' => 'page home'],
        'contact' => ['title' => 'page contact']
    ],
];

// puis au moment venu, appelons les différents *Controllers*

Pdic::invoke(MyController1::class, 'home', $myController1Params);
Pdic::invoke(MyController1::class, 'contact', $myController1Params);

// var_dump
// --------
// string(27) "controller title: page home"
// string(27) "engine: i'm the home engine"
// string(31) "renderer: i'm the HTML renderer"
// string(30) "rendered message: ma page home"
// string(24) "------------------------"
// string(19) "title: page contact"
// string(30) "engine: i'm the contact engine"
// string(31) "renderer: i'm the HTML renderer"
// string(33) "rendered message: ma page contact"
// string(24) "------------------------"
```

<br>

<a name="licence"></a>

## Licence et Utilisations

La licence donne à toute personne recevant le logiciel (et ses fichiers) le droit illimité de l'utiliser, le copier, le modifier, le fusionner, le publier, le distribuer, le vendre et le « sous-licencier » (l'incorporer dans une autre licence). La seule obligation est d'incorporer la notice de licence et de copyright dans toutes les copies.


> Copyright (c)2021 Christophe LEMOINE
>
> Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
>
> The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
>
> THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


<br>

<a name="apropos"></a>

## A propos

> *Auteur*: Christophe LEMOINE<br>
> *Email*: pantaflex@hotmail.fr<br>
> *Url*: [https://github.com/pantaflex44/Pdic](https://github.com/pantaflex44/Pdic)<br>
> *Copyright*: Copyright (c)2021 Christophe LEMOINE<br>
> *Licence*: [MIT License](https://github.com/pantaflex44/Pdic/LICENSE)

<br>