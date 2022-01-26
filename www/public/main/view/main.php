<?php
defined('TheEnd') || die('Oops, has error!');
?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-11 col-xl-8">

            <div class="card mt-5">
                <h5 class="card-header">Укажите линк</h5>
                <div class="card-body">
                    <form class="ajax-form" ajax-action="generate_link" ajax-fc="generateLink" id="form">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="https://google.com/hYehGG" name="link" required />
                            <button type="submit" class="btn btn-success">Сгенерировать</button>
                        </div>
                    </form>
                    <div id="output" hidden>
                        <p>Ваш ссылка сгенерирована</p>
                        <input type="text" class="form-control" value="" onfocus="$(this).select();">

                        <div class="btn btn-info mt-3" id="create">Создать еще одну ссылку</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
