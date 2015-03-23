Greut (Le système de vue par [Yoan Blanc](https://github.com/greut/template))
-----

Pour utiliser le système de vue il faut le déclarer dans notre fichier Public/index.php
avec ainsi :
```PHP
$framework       = new /Sohoa/Framework/Framework();
$framework->view = new /Sohoa/Framework/View/Greut();
$framework->run();
```

et dans le controller Application/Controller/Main.php
```PHP
public function IndexAction(){
    $this->greut->render(); // Cette action va automatiquement allez chercher la vue ./Application/View/Main/Index.tpl.php
}
```

la méthode render() peut prendre deux types de données en paramètre
```PHP
$this->greut->render(['myControllerName' , 'myActionName']); // ./Application/View/myControllerName/myActionName.tpl.php
```
ainsi que
```PHP
$this->greut->render('/an/path/to/the/view.tpl.php');
```

##### Les données avec greut

Nous avons un mécanisme assez simple pour la gestion des données variables dans Greut ainsi dans le controlleur si l'on définie la variable
```PHP
$this->data->foo = 'bar';
```

Nous pourrons y acceder dans la vue associée via
```PHP
echo $foo; // qui retournera bar
```