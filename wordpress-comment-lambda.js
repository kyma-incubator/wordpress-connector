module.exports = { main: function (event, context) {
  console.log('Comment ID: ' + event.data.commentId);
  console.log('Author e-mail: ' + event.data.commentAuthorEmail);
  console.log('Content: ' + event.data.commentContent);
}}
