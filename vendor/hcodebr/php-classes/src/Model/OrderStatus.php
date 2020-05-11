<?php

namespace Hcode\Model;

use Hcode\DB\Sql;
use Hcode\Model;

Class OrderStatus extends Model
{

    const EM_ABERTO = 1;
    const AGUARDANDO_PAGAMENTO = 2;
    const PAGAMENTO_APROVADO = 3;
    const ENTREGUE = 4;

}