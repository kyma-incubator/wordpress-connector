const request = require('request');


module.exports = { main: function (event, context) {

  console.log('Comment ID: ' + event.data.commentId);
  console.log('Author e-mail: ' + event.data.commentAuthorEmail);
  console.log('Content: ' + event.data.commentContent);

  if(event.data.commentContent.search("Kyma")){
        var username = "test";
        var password = "test";
        var wordpressUrl = 'http://10.182.179.49:8000/wp-json/wp/v2/comments/' + event.data.commentId;
        var proxyUrl = 'https://10.105.32.52:9090';

        console.log(wordpressUrl);
        var auth = "Basic " + new Buffer(username + ":" + password).toString("base64");



        var proxiedRequest = request.defaults({
            'proxy': proxyUrl,
            strictSSL: false
        });

        proxiedRequest.delete({
            url: wordpressUrl,
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
