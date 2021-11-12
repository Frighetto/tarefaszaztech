<?php

$ip = "127.0.0.1";
$username = "root";
$password = "";
$database = "zz";

$port = 3306;
$mysqli = new mysqli($ip, $username, $password, $database, $port);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

if ($mysqli->query("CREATE DATABASE " . $database) === TRUE) {
    $mysqli->close();
    $mysqli = new mysqli($ip, $username, $password, $database, $port);    
    $mysqli->query("CREATE table estados (id serial, rotulo varchar(45) not null)");     
    $mysqli->query("insert into estados (rotulo) values ('Pendente')");
    $mysqli->query("insert into estados (rotulo) values ('Em Andamento')");
    $mysqli->query("insert into estados (rotulo) values ('Concluído')");
    $mysqli->query("CREATE table prioridades (id serial, rotulo varchar(45) not null)");
    $mysqli->query("insert into prioridades (rotulo) values ('Baixa')");
    $mysqli->query("insert into prioridades (rotulo) values ('Media')");
    $mysqli->query("insert into prioridades (rotulo) values ('Alta')");
    $mysqli->query("CREATE table tarefas (id serial, pessoa varchar(45), prioridade bigint unsigned, estado bigint unsigned)");
    $mysqli->query("ALTER TABLE tarefas ADD FOREIGN KEY (prioridade) REFERENCES prioridades(id)");
    $mysqli->query("ALTER TABLE tarefas ADD FOREIGN KEY (estado) REFERENCES estados(id)");    
}    

$tarefa = isset($_REQUEST["tarefa"]) ? $_REQUEST["tarefa"] : null;
$pessoa = isset($_REQUEST["pessoa"]) ? $_REQUEST["pessoa"] : null;
$prioridade = isset($_REQUEST["prioridade"]) ? $_REQUEST["prioridade"] : null;
$estado = isset($_REQUEST["estado"]) ? $_REQUEST["estado"] : null;

$id = isset($_REQUEST["id"]) ? $_REQUEST["id"] : null;

$quantidade = 0;
if($tarefa != null){
    salvar($mysqli, $tarefa, $pessoa, $prioridade, $estado);
} else if($id != null){
    remover($mysqli, $id);
}

function select($resultQuery){
    for ($row_no = $resultQuery->num_rows - 1; $row_no >= 0; $row_no--) {
        $resultQuery->data_seek($row_no);
        $row = $resultQuery->fetch_assoc();  
        $rotulo = $row['rotulo'] == null ? $row['id'] : $row['rotulo'];
        echo "<option value=\"" . $row['id'] . "\">" . $rotulo . "</option>";
    }
}

function salvar($mysqli, $tarefa, $pessoa, $prioridade, $estado){       
    if($tarefa == "Nova Tarefa"){    
        global $quantidade;
        if($pessoa != null){           
             $result = $mysqli->query("SELECT count(*) as limite FROM tarefas where pessoa = '" . $pessoa . "'")->fetch_row();
             $quantidade = $result[0];            
        }                
        if($quantidade < 3){
            $sql = "INSERT INTO tarefas (pessoa, prioridade, estado)";
            if($pessoa == null){
                $sql .= "VALUES (null, " . $prioridade . ", " . $estado . ")";
            } else {
                $sql .= "VALUES ('" . $pessoa . "', " . $prioridade . ", " . $estado . ")";
            }                       
            if ($mysqli->query($sql) === FALSE) {
                echo "Error: " . $sql . "<br>" . $mysqli->error;
            }    
        }     
    } else {        
        $sql = "UPDATE tarefas SET  estado = " . $estado;       
        $sql .= " WHERE id = " . $tarefa;                
        if ($mysqli->query($sql) === FALSE) {
            echo "Error: " . $sql . "<br>" . $mysqli->error;
        }                 
    }    
}

function remover($mysqli, $id){
    $mysqli->query("DELETE FROM tarefas WHERE id = " . $id);
}

$prioridades = $mysqli->query("SELECT * FROM prioridades");
$estados = $mysqli->query("SELECT * FROM estados");
$tarefas = $mysqli->query(
"SELECT tarefas.id, 
    tarefas.pessoa, 
    prioridades.rotulo as prioridade, 
    estados.rotulo as estado
 FROM tarefas 
 left join prioridades on tarefas.prioridade = prioridades.id 
 left join estados on tarefas.estado = estados.id");


$mysqli->close();
?>

<html>
    <header>
        <title>Tarefas</title>
        <link rel="stylesheet" href="bootstrap-5.1.3-dist/css/bootstrap.css">
        <script src="bootstrap-5.1.3-dist/js/bootstrap.js"></script>
        <script>
            function verificarExcluir(){
                var tarefa = document.getElementById("tarefa").value;
                document.getElementById("excluir").disabled = tarefa == "Nova Tarefa";
                document.getElementById("pessoa").disabled = tarefa == "Nova Tarefa";
                document.getElementById("prioridade").disabled = tarefa == "Nova Tarefa";
            }            
            function salvar(){
                var tarefa = document.getElementById("tarefa").value;
                var pessoa = document.getElementById("pessoa").value;
                var prioridade = document.getElementById("prioridade").value;
                var estado = document.getElementById("estado").value;
                window.location.replace("index.php?tarefa=" + tarefa 
                + "&pessoa=" + pessoa + "&prioridade=" + prioridade + "&estado=" + estado);
            }
            function excluir(){
                var tarefa = document.getElementById("tarefa").value;
                window.location.replace("index.php?id=" + tarefa);
            }
           
            <?php 
                global $quantidade;               
                if($quantidade >= 3){
                    echo "alert('Não pode haver mais que 3 tarefas a mesma pessoa')";
                }
            ?>
        </script>
    </header>
    <body>

        <div class="input-group mb-3" style="width: 50%; margin: auto">           
            <select id="tarefa" onchange="verificarExcluir()" class="form-select" aria-label="tarefa">
                <option selected>Nova Tarefa</option>
                <?php select($tarefas); ?>       
            </select>
            <span class="input-group-text"> </span>
            <input id="pessoa" type="text" class="form-control" placeholder="Pessoa" aria-label="Pessoa">
            <span class="input-group-text"> </span>
            <select id="prioridade" class="form-select" aria-label="Prioridade">
                <?php select($prioridades); ?>       
            </select>
            <span class="input-group-text"> </span>  
            <select id="estado" class="form-select" aria-label="Estado">
                <?php select($estados); ?>   
            </select>                 
        </div> 
        <div class="input-group mb-3" style="width: 50%; margin: auto"> 
            <button type="button" onclick="salvar()" class="btn btn-success">Salvar</button>
            <button id="excluir" onclick="excluir()" type="button" class="btn btn-danger" disabled>Excluir</button>      
        </div>

        <table class="table" style="width: 50%; margin: auto">
            <thead>
                <tr>
                <th style="text-align: center" scope="col">#</th>
                <th scope="col">Pessoa</th>
                <th style="text-align: center" scope="col">Prioridade</th>
                <th style="text-align: center" scope="col">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    for ($row_no = $tarefas->num_rows - 1; $row_no >= 0; $row_no--) {
                        $tarefas->data_seek($row_no);
                        $row = $tarefas->fetch_assoc();
                        echo "<tr>
                                <th style=\"text-align: center\" scope=\"row\">" .  $row['id'] . "</th>
                                <td>" .  $row['pessoa'] . "</td>
                                <td style=\"text-align: center\">" .  $row['prioridade'] . "</td>
                                <td style=\"text-align: center\">" .  $row['estado'] . "</td>
                            </tr>";            
                    }
                ?>    
            </tbody>
        </table>
    </body>
</html>