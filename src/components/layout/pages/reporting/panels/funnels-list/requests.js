const MongoClient = require("mongodb").MongoClient;
const assert = require("assert");
require("dotenv").config();
const uuid = require("uuid/v4");
const { DateTime } = require("luxon");
const fs = require("fs");

const mongoURL = process.env.MONGODBURL;
const dbName = process.env.MONGODBNAME;
const mongoSettings = {
  // useNewUrlParser: true,
  autoReconnect: true,
  // reconnectTries: Number.MAX_VALUE,
  reconnectInterval: 1000,
  poolSize: 10,
  // poolSize: 10, // Number of concurrent connections
  connectTimeoutMS: 5 * 60 * 3000
};

const requestsData = [
  {
    name: "HR Live Headcount",
    description: "All current Ubisoft employees.",
    _id: uuid(),
    platform: "microstrategy",
    reportId: "25E8CAF3480BE3EB7D3840A769BFFFB1",
    projectId: "5E40E6CC45263F65CC667AB161ECD73E",
    hashes: ["hrlive"],
    collection: "hrlive",
    dataTag: "hrlive-headcount",
    createdBy: "metrix",
    createdAt: DateTime.local(),
    glossary:
      "<div> <div className='info-title'>Glossary</div> <div className='content'> <div className='element ng-star-inserted'> <a className='term'> Headcount </a> <quill-view-html theme='snow'> <div className='ql-container ql-snow ngx-quill-view-html'> <div className='ql-editor'> <p> <strong> <u>Definition:</u> </strong> People on a permanent or fixed-term contract who are subject to a full or partial HR follow-up (salary review, appraisal, training, etc.) and/or who are entitled to benefits due to their employment status at Ubisoft (paid vacation, access to an internal employee privileges program, private healthcare, etc.) </p> <p></p> <p> <strong> <u>Calculation:</u> </strong> <em>Headcount Type = \"Permanent\" or Employee Category = Ubi staff + Ubi staff Consultants + Ubi staff International Mobility + Ubi staff National Mobility</em> </p> <p> <em>Employee Status = \"Active\"</em> </p> <p> <em>Headcount is displayed with End Of Month values for all past months, and for Current Date on the current month</em> </p> </div> </div> </quill-view-html> </div> <div className='element ng-star-inserted'> <a className='term'> Manager </a> <quill-view-html theme='snow'> <div className='ql-container ql-snow ngx-quill-view-html'> <div className='ql-editor'> <p> <strong> <u>Definition:</u> </strong> Collaborator currently working at Ubisoft, and in a Management Position </p> <p></p> <p> Calculation: <em>Employee with a Status 'Active' and a Management Position 'Manager'</em> </p> <p> <em>The \"Management Position\" is calculated as a flag at professional page level. If a specific HRTB ID is identified as a MANAGER_EMPLOYEE_ID =&gt; the person is 'manager'. If not=&gt; 'employee'.</em> </p> <p> <em>Basically, if a person is marked as manager in HRTB, the management position from MSTR will be \"Manager\".</em> </p> </div> </div> </quill-view-html> </div> <div className='element ng-star-inserted'> <a className='term'> Seniority </a> <div className='ql-container ql-snow ngx-quill-view-html'> <div className='ql-editor'> <p>Number of years between the First Hire Date of a collaborator, and the date analyzed. If a collaborator left Ubisoft and came back, the time he spent away is substracted from the calculation.</p> <p> <em>For example, an employee who joined 5 years ago, but left the company after 2 years and came back 1 year ago, has a Seniority of 3 years.</em> </p> </div> </div> </div> </div></div>"
  },
  {
    name: "HR Live Hires",
    description: "All new Ubisoft hires.",
    _id: uuid(),
    platform: "microstrategy",
    reportId: "2387432642A4FAD69DF528A394739035",
    projectId: "5E40E6CC45263F65CC667AB161ECD73E",
    hashes: ["hrlive"],
    collection: "hrlive",
    dataTag: "hrlive-hires",
    createdBy: "metrix",
    createdAt: DateTime.local()
  },
  {
    name: "HR Live Turnover",
    description: "All  Ubisoft turnovers.",
    _id: uuid(),
    platform: "microstrategy",
    reportId: "F1FA95564B79DC72B5F863A32C46D826",
    projectId: "5E40E6CC45263F65CC667AB161ECD73E",
    hashes: ["hrlive"],
    collection: "hrlive",
    dataTag: "hrlive-turnovers",
    createdBy: "metrix",
    createdAt: DateTime.local()
  }

  // {
  //   name: "Intelligent Cube Reserves",
  //   description: "All new Ubisoft hires.",
  //   _id: uuid(),
  //   platform: "microstrategy",
  //   reportId: "B6CA26AB4A2CDAB7AF78E1ABFE21AC24",
  //   projectId: "6A80E89944A2BD5AF4AEE48F419B09C7",
  //   collection: "global-sales",
  //   hashes: ["globalsales"],
  //   dataTag: "global-sales",
  //   createdBy: "metrix",
  //   createdAt: DateTime.local()
  // },
  // {
  //   name: "Confidence Matrix",
  //   description: "All new Ubisoft hires.",
  //   _id: uuid(),
  //   platform: "microstrategy",
  //   reportId: "F721A29249E9E3865E9BD499CD49B63B",
  //   projectId: "6A80E89944A2BD5AF4AEE48F419B09C7",
  //   collection: "global-sales",
  //   hashes: ["globalsales"],
  //   dataTag: "global-sales",
  //   createdBy: "metrix",
  //   createdAt: DateTime.local()
  // },
  // {
  //   name: "Reserves US Weeks",
  //   description: "All new Ubisoft hires.",
  //   _id: uuid(),
  //   platform: "microstrategy",
  //   reportId: "9E8B9413437EA6FD0D4D3FABBDF20E5B",
  //   projectId: "6A80E89944A2BD5AF4AEE48F419B09C7",
  //   collection: "global-sales",
  //   hashes: ["globalsales"],
  //   dataTag: "global-sales",
  //   createdBy: "metrix",
  //   createdAt: DateTime.local()
  // }
];

// Functions are defined let's connect!
MongoClient.connect(mongoURL, (err, client) => {
  assert.equal(null, err);
  const db = client.db(dbName);

  const requestsCollection = db.collection("requests");

  requestsCollection.remove({});
  requestsData.forEach(request => {
    requestsCollection.insertOne(request, function(err, result) {
      console.log(request.name + " request was added to Metrix.");
    });
  });

  console.log("done with requests");
});
