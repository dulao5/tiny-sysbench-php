package computerdatabase

import scala.concurrent.duration._

import io.gatling.core.Predef._
import io.gatling.http.Predef._

class BasicSimulation extends Simulation {

  var maxClients = 25

  val httpProtocol = http
    .baseUrl("http://nginx")
    .acceptHeader("text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8")
    .doNotTrackHeader("1")
    .acceptLanguageHeader("en-US,en;q=0.5")
    .acceptEncodingHeader("gzip, deflate")
    .userAgentHeader("Mozilla/5.0 (Windows NT 5.1; rv:31.0) Gecko/20100101 Firefox/31.0")
    .maxConnectionsPerHost(maxClients)
    .shareConnections

  val scn = scenario("BasicSimulation")
    .exec(
      http("request_index")
        .get("/")
    )
    //.pause(5)

  setUp(
    scn.inject(
        constantConcurrentUsers(maxClients) during(300 seconds)
    )
  ).protocols(httpProtocol)
}
