# 1.0.1
- The mail preview can resolve the productReviewLinks variable
- The mail template uses productReviewLinks|default([])

# 1.0.0
- Newsletter checkbox on the checkout confirm page
- The review mail is sent only when the newsletter was accepted
- The waiting time starts the first time the delivery reaches Shipped
- After the configured number of days the flow "Produktbewertung einfordern" is triggered
- Mail template with a link to the review form on the product page
- Flow, mail template and queue are created during installation
