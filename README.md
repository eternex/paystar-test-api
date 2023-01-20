# PAY-STAR PaymentGate implementation on Laravel.
### This project designed as a test.
#
## APIs
Get a new fake cart to show in UI.

```
GET /carts/get-new-fake-cart 
    Input Params: -

    Result:
        order_id: int -> in each request it is new ,
        cart_id: int -> in each request it is new,
        cart: [
            {
                product_name: string,
                product_price: int,
                product_quantity: int
            },
            ...
        ],
        amount: int -> show final price of cart
```

To Open IPG for pay
```
POST /payment-gate/request-to-pay
    Input Params:
        cart_id: int -> id of cart that user want to pay
        card_number: string -> card number of user that pay must finished with it. 
    
    Result:
        (success of failure can be detected by HTTP status CODE)
        status: bool -> if a token for IPG maked success it's be true
        token: string -> token that needed to open PayStar IPG
        message: string -> in case of error, show a message about what happening to user.
```

To get stauts of payment action by invoice id.
```
GET /payment-gate/get-payment/{invoice_id}
    Input Params: - 

    Result:
        (in case that invoice found)
        invoice_id: int,
        tracking_code: int,
        payment_amount: int,
        status: bool -> status of payment,
        message: if case that payment is not success describe the problem

        (in not found mode) => 404 HTTP CODE
        message: string -> show 404 message.
```
#
## UP and Running
This project implement on Sail stack, an environment to run laravel project on Docker containers.
To run this project consinder that you must install 'Docker' and 'Docker Compose'.


To run project, inside directory of project run bellow command:
```
    ./vendor/bin/sail up
```
#
## Database diagram
![Image alt text](/extras/diagram.png)

In each reload in checkout page make a new fake cart for customer ('cart' and 'cart_detail' table).

In each request for payment make a new invoice that stored in 'invoice' table, After operaion finsihed in IPG (success or fail payment doesn' matter), The 'invoice_paid' table filled,