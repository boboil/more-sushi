<template>
    <div class="wrapper">
        <header class="header">
            <div class="container">
                <button class="burger-btn">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <div class="logo">
                    <a href="/">
                        <img src="/img/logo.svg" alt="">
                    </a>
                </div>
                <div class="collapsed">
                    <nav class="menu">
                        <ul>
                            <li>
                                <a href="/" class="active">Головна</a>
                            </li>
                            <li>
                                <a href="/">Меню</a>
                            </li>
                            <li>
                                <a href="/">Акції</a>
                            </li>
                            <li>
                                <a href="/">Доставка</a>
                            </li>
                            <li>
                                <a href="/">Контакти</a>
                            </li>
                        </ul>
                    </nav>

                    <div class="contacts">
                        <a href="https://instagram.com/darymorya.sushi.sumy" rel="nofollow noopener" class="insta" target="_blank">
                            <img src="/img/icons/icon-insta.png" alt="">
                        </a>
                        <a href="tel:0990663511" class="phone">
                            <img src="/img/icons/icon-phone.png" alt="">
                            <span>
									099-066-35-11
								</span>
                        </a>
                    </div>
                </div>

                <!-- выводи корзину если есть продукты -->
                <div class="cart">
                    <button class="cart-btn">
                        <img src="/img/icons/icon-cart.png" alt="">
                        <span>1</span>
                    </button>
                    <div class="cart-inner">
                        <table class="cart-table">
                            <thead>
                            <tr>
                                <th>
                                    Продукт
                                </th>
                                <th>
                                    Цiна
                                </th>
                                <th>
                                    Кiлькiсть
                                </th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>
                                    <div class="cart-product-info">
                                        <button></button>
                                        <img
                                            src="https://tokyo-sushi.com.ua/storage/products/7f/7f4c521c0ec05990c6e6327e17a16a7f4929f1852050.jpeg"
                                            alt="">
                                        <h3>
                                            Акція Сет "Флай"
                                        </h3>
                                    </div>
                                </td>
                                <td>
                                    <div class="cart-product-price">
                                        519 <span>грн</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cart-product-quantity">
                                        <button>-</button>
                                        <input type="number" min="1" max="9" value="1">
                                        <button>+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="cart-product-info">
                                        <button></button>
                                        <img
                                            src="https://tokyo-sushi.com.ua/storage/products/7f/7f4c521c0ec05990c6e6327e17a16a7f4929f1852050.jpeg"
                                            alt="">
                                        <h3>
                                            Акція Сет "Флай"
                                        </h3>
                                    </div>
                                </td>
                                <td>
                                    <div class="cart-product-price">
                                        519 <span>грн</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cart-product-quantity">
                                        <button>-</button>
                                        <input type="number" min="1" max="9" value="1">
                                        <button>+</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="cart-product-info">
                                        <button></button>
                                        <img
                                            src="https://tokyo-sushi.com.ua/storage/products/7f/7f4c521c0ec05990c6e6327e17a16a7f4929f1852050.jpeg"
                                            alt="">
                                        <h3>
                                            Акція Сет "Флай"
                                        </h3>
                                    </div>
                                </td>
                                <td>
                                    <div class="cart-product-price">
                                        519 <span>грн</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="cart-product-quantity">
                                        <button>-</button>
                                        <input type="number" min="1" max="9" value="1">
                                        <button>+</button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="cart-summary">
                            <div class="cart-delivery-annotation">
                                Безкоштовна доставка від 650 грн
                            </div>
                            <div class="cart-total">
                                До сплати: <span>1218 грн</span>
                            </div>
                        </div>
                        <button class="btn green cart-proceed">оформити замовлення</button>
                    </div>
                </div>
            </div>
        </header>

        <main class="content">
            <section class="block-homescreen">
                <div class="container">
                    <div class="text">
                        <h1>
                            Суші <br>
                            <span>що смакують</span>
                        </h1>
                        <div class="descr">
                            <p>
                                Дякуємо що завітали на наш сайт! <br>
                                Ми сподіваємося, що Ви знайдете те, що буде Вам до смаку. Тільки справжній сир Філадельфія, тільки краща
                                риба від виробника, і найкращі інгредієнти - ніяких компромісів! <br>
                                Ми , як ніхто знаємося на сушi , переконайтесь в цьому i Ви!
                            </p>
                        </div>
                        <a href="/categories.html" class="btn green">
                            Меню
                        </a>
                    </div>
                    <div class="banner">
                        <img src="/img/home-banner.png" alt="">
                    </div>
                </div>
            </section>

            <section class="block-categories">
                <div class="container">
                    <h2 class="block-title">
                        Меню
                    </h2>
                    <ul class="categories-list">
                        <li>
                            <a href="/" class="category-item">
                                <div class="category-item-photo">
                                    <img src="/img/temp/category-premium.jpg" alt="">
                                </div>
                                <div class="category-item-title">
                                    Преміум-роли
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/" class="category-item">
                                <div class="category-item-photo">
                                    <img src="/img/temp/category-rolls.jpg" alt="">
                                </div>
                                <div class="category-item-title">
                                    Роли
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/" class="category-item">
                                <div class="category-item-photo">
                                    <img src="/img/temp/category-sushi.jpg" alt="">
                                </div>
                                <div class="category-item-title">
                                    Суші
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/" class="category-item">
                                <div class="category-item-photo">
                                    <img src="/img/temp/category-sets.jpg" alt="">
                                </div>
                                <div class="category-item-title">
                                    Сети
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/" class="category-item">
                                <div class="category-item-photo">
                                    <img src="/img/temp/category-snacks.jpg" alt="">
                                </div>
                                <div class="category-item-title">
                                    Закуски
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/" class="category-item">
                                <div class="category-item-photo">
                                    <img src="/img/temp/category-drinks.jpg" alt="">
                                </div>
                                <div class="category-item-title">
                                    Напої
                                </div>
                            </a>
                        </li>
                        <li>
                            <a href="/" class="category-item">
                                <div class="category-item-photo">
                                    <img src="/img/temp/category-salads.jpg" alt="">
                                </div>
                                <div class="category-item-title">
                                    Гарніри
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="block-category-products">
                <div class="container">
                    <!-- если на странице одной категории то н1 -->
                    <h2 class="block-title">
                        Роли
                    </h2>
                    <!-- в одной категории тут также блок с подкатегориями -->
                    <div class="category-products-list">
                        <div class="product-item">
                            <div class="product-item-label orange">Акция</div>
                            <div class="product-item-photo">
                                <img src="/img/temp/category-rolls.jpg" alt="" loading="lazy">
                            </div>
                            <div class="product-item-info">
                                <div class="product-item-title">
                                    Сирний рол з лососем
                                </div>
                                <div class="product-item-descr">
                                    <p>
                                        Склад: <br>
                                        рис, водорості норі, сир «Філадельфія», лосось, манго, сир чеддер.&nbsp;
                                    </p>
                                </div>
                                <div class="product-item-params">
                                    <div class="product-item-weight">
                                        260 г
                                    </div>
                                    <div class="product-item-price">
                                        <span class="old-price">155</span>
                                        <span class="current-price">125</span>
                                        <span class="currency">
												грн
											</span>
                                    </div>
                                </div>
                                <button class="btn green small product-item-add">
                                    в кошик
                                </button>
                                <!-- <button class="btn orange small product-item-add">
                                  в кошику
                                </button> -->
                            </div>
                        </div>
                        <div class="product-item">
                            <!-- <div class="product-item-label orange">Акция</div> -->
                            <div class="product-item-photo">
                                <img src="/img/temp/category-rolls.jpg" alt="" loading="lazy">
                            </div>
                            <div class="product-item-info">
                                <div class="product-item-title">
                                    Сирний рол з лососем
                                </div>
                                <div class="product-item-descr">
                                    <p>
                                        Склад: <br>
                                        рис, водорості норі, сир «Філадельфія», лосось, манго, сир чеддер.&nbsp;
                                    </p>
                                </div>
                                <div class="product-item-params">
                                    <div class="product-item-weight">
                                        260 г
                                    </div>
                                    <div class="product-item-price">
                                        <span class="current-price">125</span>
                                        <span class="currency">
												грн
											</span>
                                    </div>
                                </div>
                                <button class="btn green small product-item-add">
                                    в кошик
                                </button>
                                <!-- <button class="btn orange small product-item-add">
                                  в кошику
                                </button> -->
                            </div>
                        </div>
                        <div class="product-item">
                            <div class="product-item-label green">Новинка</div>
                            <div class="product-item-photo">
                                <img src="/img/temp/category-rolls.jpg" alt="" loading="lazy">
                            </div>
                            <div class="product-item-info">
                                <div class="product-item-title">
                                    Сирний рол з лососем
                                </div>
                                <div class="product-item-descr">
                                    <p>
                                        Склад: <br>
                                        рис, водорості норі, сир «Філадельфія», лосось, манго, сир чеддер.&nbsp;
                                    </p>
                                </div>
                                <div class="product-item-params">
                                    <div class="product-item-weight">
                                        260 г
                                    </div>
                                    <div class="product-item-price">
                                        <span class="current-price">155</span>
                                        <span class="currency">
												грн
											</span>
                                    </div>
                                </div>
                                <button class="btn green small product-item-add">
                                    в кошик
                                </button>
                                <!-- <button class="btn orange small product-item-add">
                                  в кошику
                                </button> -->
                            </div>
                        </div>
                    </div>
                    <button class="loadmore">
                        <span>показати більше</span>
                        <span class="circle"></span>
                        <span class="circle"></span>
                        <span class="circle"></span>
                    </button>
                </div>
            </section>
            <section class="block-category-products">
                <div class="container">
                    <h2 class="block-title">
                        Суші
                    </h2>
                    <div class="category-products-list">
                        <div class="product-item">
                            <div class="product-item-label orange">Акция</div>
                            <div class="product-item-photo">
                                <img src="/img/temp/category-rolls.jpg" alt="" loading="lazy">
                            </div>
                            <div class="product-item-info">
                                <div class="product-item-title">
                                    Сирний рол з лососем
                                </div>
                                <div class="product-item-descr">
                                    <p>
                                        Склад: <br>
                                        рис, водорості норі, сир «Філадельфія», лосось, манго, сир чеддер.&nbsp;
                                    </p>
                                </div>
                                <div class="product-item-params">
                                    <div class="product-item-weight">
                                        260 г
                                    </div>
                                    <div class="product-item-price">
                                        <span class="old-price">155</span>
                                        <span class="current-price">125</span>
                                        <span class="currency">
												грн
											</span>
                                    </div>
                                </div>
                                <button class="btn green small product-item-add">
                                    в кошик
                                </button>
                                <!-- <button class="btn orange small product-item-add">
                                  в кошику
                                </button> -->
                            </div>
                        </div>
                        <div class="product-item">
                            <!-- <div class="product-item-label orange">Акция</div> -->
                            <div class="product-item-photo">
                                <img src="/img/temp/category-rolls.jpg" alt="" loading="lazy">
                            </div>
                            <div class="product-item-info">
                                <div class="product-item-title">
                                    Сирний рол з лососем
                                </div>
                                <div class="product-item-descr">
                                    <p>
                                        Склад: <br>
                                        рис, водорості норі, сир «Філадельфія», лосось, манго, сир чеддер.&nbsp;
                                    </p>
                                </div>
                                <div class="product-item-params">
                                    <div class="product-item-weight">
                                        260 г
                                    </div>
                                    <div class="product-item-price">
                                        <span class="current-price">155</span>
                                        <span class="currency">
												грн
											</span>
                                    </div>
                                </div>
                                <button class="btn green small product-item-add">
                                    в кошик
                                </button>
                                <!-- <button class="btn orange small product-item-add">
                                  в кошику
                                </button> -->
                            </div>
                        </div>
                        <div class="product-item">
                            <div class="product-item-label green">Новинка</div>
                            <div class="product-item-photo">
                                <img src="/img/temp/category-rolls.jpg" alt="" loading="lazy">
                            </div>
                            <div class="product-item-info">
                                <div class="product-item-title">
                                    Сирний рол з лососем
                                </div>
                                <div class="product-item-descr">
                                    <p>
                                        Склад: <br>
                                        рис, водорості норі, сир «Філадельфія», лосось, манго, сир чеддер.&nbsp;
                                    </p>
                                </div>
                                <div class="product-item-params">
                                    <div class="product-item-weight">
                                        260 г
                                    </div>
                                    <div class="product-item-price">
                                        <span class="current-price">155</span>
                                        <span class="currency">
												грн
											</span>
                                    </div>
                                </div>
                                <button class="btn green small product-item-add">
                                    в кошик
                                </button>
                                <!-- <button class="btn orange small product-item-add">
                                  в кошику
                                </button> -->
                            </div>
                        </div>
                    </div>
                    <button class="loadmore">
                        <span>показати більше</span>
                        <span class="circle"></span>
                        <span class="circle"></span>
                        <span class="circle"></span>
                    </button>
                </div>
            </section>

            <section class="block-seo">
                <div class="container">
                    <p>
                        Море Суші – це завжди свіжа рибка, смачна колаборація інгредієнтів та неперевершений сервіс!
                    </p>
                    <p>
                        Японська кухня заполонила світ своїми незвичними поєднаннями: манго та лосось, авокадо та тунець, такуан та
                        креветка...можна перелічувати їх годинами, але все ж таки краще спробувати на смак!
                    </p>
                    <p>
                        Чому варто обрати заклад “Море Суші” у місті Суми:<br>
                        - ми створюємо традиційні японські страви тільки з якісних інгредієнтів;<br>
                        - маємо кваліфікований персонал, що надає послуги на найвижчому рівні;<br>
                        - наші постачальники завжди слідкують за якістю продукції та мають великий досвід та високий рейтинг серед
                        інших;<br>
                        - кухня, де ми готуємо смачнющі суші та роли, відповідає всім стандартам за чистотою та обладнанням.
                    </p>
                    <p>
                        Для наших клієнтів ми створили зручний сервіс доставки, встановили доступні ціни на всі позиції меню: від
                        закусок до преміум-ролів та сетів.
                        <br>
                        Також, заклад “МореСуші” відрізняється своїм широким асортиментом у меню. У нас і гарніри, і закуски, сети
                        та окремо суші, роли, преміум-роли, а також напої.
                        <br>
                        Ми пишаємося нашими перевагами та радимо спробувати сервіс “Море Суші”!
                    </p>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="container">
                <div class="contacts">
                    <a href="tel:0990663511" class="phone">
                        <img src="/img/icons/icon-phone.png" alt="">
                        <span>
								099-066-35-11
							</span>
                    </a>

                    <address class="address">
                        <a href="https://maps.google.com/maps?daddr=50.915289473323675,34.814197684261224"
                           rel="noindex nofollow noreferrer" target="_blank">
                            <img src="/img/icons/icon-marker.png" alt="">
                            <span>
									м. Суми, вул. Троїцька, 17
								</span>
                        </a>
                    </address>
                </div>
                <div class="copyright">
                    © 2022 All rights reserved
                </div>
                <div class="logo">
                    <img src="/img/logo.svg" alt="">
                </div>
            </div>
        </footer>

    </div>
</template>

<script>
export default {
    name: "IndexComponent"
}
</script>

<style scoped>

</style>
