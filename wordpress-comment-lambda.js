const request = require('request');


module.exports = { main: function (event, context) {

  console.log('Comment ID: ' + event.data.commentId);
  console.log('Author e-mail: ' + event.data.commentAuthorEmail);
  console.log('Content: ' + event.data.commentContent);

  if(event.data.commentContent.search("Kyma")===-1){
        var username = "test";
        var password = "test";
        var url = `${process.env.GATEWAY_URL}/comments/${event.data.commentId}`;
        var auth = "Basic " + new Buffer(username + ":" + password).toString("base64");

        request.delete({
            url: url,
            headers: {
                "Authorization": auth
            }
        },
        function (error, httpResponse, body) {
            if (error === null) {
                console.log('Deleted comment with id: ' + event.data.commentId);
                console.log('HTTP code: ' + httpResponse);
                console.log('Response body: ' + body);
            } else {
                console.log(error);
                console.log('Failed to delete comment');

            }
        });
  }
}
};
